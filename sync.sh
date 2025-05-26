#!/bin/bash
# orignal from https://discourse.roots.io/t/leveraging-wp-cli-aliases-in-your-wordpress-development-workflow/8414/12?u=allurewebsolutions

DEVDIR="web/app/uploads/"
DEVSITE="heatstrike.test"

PRODDIR="forge@mick.heatstrike.uk:/home/forge/mick.heatstrike.uk/shared/uploads/"
PRODSITE="mick.heatstrike.uk"

FROM=$1
TO=$2

case "$1-$2" in
  dev-prod) DIR="up";  FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$PRODSITE; TODIR=$PRODDIR; ;;
#  dev-staging)    DIR="up"   FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$STAGSITE; TODIR=$STAGDIR; ;;
  prod-dev) DIR="down" FROMSITE=$PRODSITE; FROMDIR=$PRODDIR; TOSITE=$DEVSITE;  TODIR=$DEVDIR; ;;
 # dev-remotedev)    DIR="up"   FROMSITE=$DEVSITE;  FROMDIR=$DEVDIR;  TOSITE=$REMOTEDEVSITE; TODIR=$REMOTEDEVDIR; ;;
 # staging-dev)    DIR="down" FROMSITE=$STAGSITE; FROMDIR=$STAGDIR; TOSITE=$DEVSITE;  TODIR=$DEVDIR; ;;
 # staging-remotedev)    DIR="down" FROMSITE=$STAGSITE; FROMDIR=$STAGDIR; TOSITE=$REMOTEDEVSITE;  TODIR=$REMOTEDEVDIR; ;;
  *) echo "usage: $0 dev prod | dev staging | prod dev | staging dev" && exit 1 ;;
esac

read -r -p "Reset the $TO database and sync $DIR from $FROM? [y/N] " response
read -r -p "Sync the uploads folder? [y/N] " uploads

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  echo "Exporting $TO db" &&
    # export below goes into the current directory /srv/www/bookworks.org.uk/releases/XXXXX - last release date
  wp "@$TO" db export $TO-backup.sql --path=web/wp &&
  echo "Resetting $TO db" &&
  wp "@$TO" db reset --yes --path=web/wp &&
    # echo "Exporting $FROM db" &&
    # wp "@$FROM" db export --path=web/wp - > $FROM.sql &&
    # echo "Importing db" &&
    # wp "@$TO" db import ./$FROM.sql --path=web/wp && ## from production
  echo "Exporting db from @$FROM to @$TO" &&
  wp "@$FROM" db export --path=web/wp - > ./temp_export_import.sql &&
  # wp "@$TO" db import ./temp_export_import.sql --path=web/wp &&
  cat ./temp_export_import.sql | wp "@$TO" db import - --path=web/wp && ## from dev
  echo "Modifying $TO db" &&
  wp "@$TO" search-replace $FROMSITE $TOSITE --recurse-objects --skip-columns=guid --path=web/wp
fi
if [[ "$uploads" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  rsync -az --progress "$FROMDIR" "$TODIR"
fi
