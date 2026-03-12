#!/bin/bash
# Sync database and uploads between environments
# Based on https://discourse.roots.io/t/leveraging-wp-cli-aliases-in-your-wordpress-development-workflow/8414/12

DEVDIR="web/app/uploads/"
DEVSITE="http://example.test"

STAGDIR="user@staging.example.com:/home/user/staging.example.com/shared/uploads/"
STAGSITE="https://staging.example.com"

# PRODDIR="user@example.com:/home/user/example.com/shared/uploads/"
# PRODSITE="https://example.com"

FROM=$1
TO=$2

extract_host() {
  local target="$1"

  # rsync remote format: user@host:/path
  if [[ "$target" == *"@"*":"* ]]; then
    local remote="${target#*@}"
    echo "${remote%%:*}"
    return
  fi

  # URL format: scheme://host[:port][/path]
  if [[ "$target" == *"://"* ]]; then
    local without_scheme="${target#*://}"
    local host_port="${without_scheme%%/*}"
    echo "${host_port%%:*}"
    return
  fi

  echo "localhost"
}

case "$1-$2" in
  # dev-prod)     DIR="up";   FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$PRODSITE; TODIR=$PRODDIR; ;;
  dev-staging)    DIR="up";   FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$STAGSITE; TODIR=$STAGDIR; ;;
  # prod-dev)     DIR="down"; FROMSITE=$PRODSITE; FROMDIR=$PRODDIR; TOSITE=$DEVSITE;  TODIR=$DEVDIR; ;;
  staging-dev)    DIR="down"; FROMSITE=$STAGSITE; FROMDIR=$STAGDIR; TOSITE=$DEVSITE;  TODIR=$DEVDIR; ;;
  *) echo "usage: $0 dev staging | staging dev" && exit 1 ;;
esac

FROM_SITE_HOST=$(extract_host "$FROMSITE")
TO_SITE_HOST=$(extract_host "$TOSITE")
FROM_UPLOAD_HOST=$(extract_host "$FROMDIR")
TO_UPLOAD_HOST=$(extract_host "$TODIR")

echo "=============================================================="
echo "WARNING: verify sync endpoints before continuing"
echo "  DB sync:      $FROM -> $TO"
echo "    from site:  $FROMSITE ($FROM_SITE_HOST)"
echo "    to site:    $TOSITE ($TO_SITE_HOST)"
echo "  Upload sync:  $FROMDIR ($FROM_UPLOAD_HOST) -> $TODIR ($TO_UPLOAD_HOST)"
echo "=============================================================="

read -r -p "Reset the $TO database and sync $DIR from $FROM? [y/N] " response
read -r -p "Sync the uploads folder? [y/N] " uploads

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  echo "Exporting $TO db" &&
  wp "@$TO" db export $TO-backup.sql --path=web/wp &&
  echo "Resetting $TO db" &&
  wp "@$TO" db reset --yes --path=web/wp &&
  echo "Exporting db from @$FROM to @$TO" &&
  wp "@$FROM" db export --path=web/wp - > ./temp_export_import.sql &&
  # MariaDB 10.6+ can prepend a sandbox directive comment that wp db import rejects.
  # Strip it from the first line only when present.
  sed -i.bak '1s|^/\*M!999999\\- enable the sandbox mode \*/[[:space:]]*$||' ./temp_export_import.sql &&
  rm -f ./temp_export_import.sql.bak &&
  cat ./temp_export_import.sql | wp "@$TO" db import - --path=web/wp &&
  echo "Modifying $TO db" &&
  wp "@$TO" search-replace $FROMSITE $TOSITE --recurse-objects --skip-columns=guid --path=web/wp
fi
if [[ "$uploads" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  rsync -az --progress "$FROMDIR" "$TODIR"
fi
