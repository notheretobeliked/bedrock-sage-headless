<div class="{{ $block->classes }}" style="{{ $block->inlineStyle }}">
    <form method="post" class="contents">
        <div class="flex flex-col gap-3">
            {{-- Personal information grid --}}
            @if ($email || $phone || $postcode)
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <input
                            class="w-full border-white placeholder:text-black/75 border text-black rounded-md px-2 py-2 focus:bg-extremecaution transition-all duration-300 bg-caution"
                            type="text" placeholder="First name" name="firstname" required />
                    </div>
                    <div>
                      <input
                          class="w-full border-white placeholder:text-black/75 border text-black rounded-md px-2 py-2 focus:bg-extremecaution transition-all duration-300 bg-caution"
                          type="text" placeholder="Last name" name="lastname" required />
                  </div>
                    @if ($email)
                        <div>
                            <input
                                class="w-full border-white placeholder:text-black/75 border text-black rounded-md px-2 py-2 focus:bg-extremecaution transition-all duration-300 bg-caution"
                                type="email" placeholder="Email Address" name="email" required />
                        </div>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-3">

                    @if ($postcode)
                        <div>
                            <input
                                class="w-full border-white placeholder:text-black/75 border text-black rounded-md px-2 py-2 focus:bg-extremecaution transition-all duration-300 bg-caution"
                                type="text" placeholder="Postcode" name="postcode" required />
                        </div>
                    @endif
                    @if ($phone)
                        <div>
                            <input
                                class="w-full border-white placeholder:text-black/75 border text-black rounded-md px-2 py-2 focus:bg-extremecaution transition-all duration-300 bg-caution"
                                type="tel" placeholder="Phone Number" name="phone" required />
                        </div>
                    @endif
                </div>
            @endif

            {{-- Trade union section --}}
            @if ($union_list)
                <div class="flex flex-col gap-3">
                    {{-- Trade union member checkbox --}}
                    <div class="relative overflow-hidden">
                        <input type="checkbox" class="check absolute w-10 h-10 text-black border-black opacity-0"
                            id="union_member" name="union_member" />
                        <label for="union_member" class="label flex flex-row items-center gap-2">
                            <div class="relative w-5 h-5">
                                <div class="absolute inset-0 border-2 border-black rounded-sm"></div>
                                <svg width="20" height="20" viewBox="0 0 400 400" class="absolute inset-0">
                                    <g>
                                        <path class="path1" fill="none" stroke-width="50" stroke="black"
                                            d="M 72.57142639160156 207.42857360839844 L 160.28571319580078 295.1428604125977 L 327.42857360839844 104.85714721679688" />
                                    </g>
                                </svg>
                            </div>
                            <span>Are you a trade union member?</span>
                        </label>
                    </div>

                    {{-- Trade union and workplace fields --}}
                    <div class="union-fields grid grid-cols-2 gap-3">
                        {{-- Trade union dropdown --}}
                        <div class="relative">
                            <select
                                class="w-full border-white text-black border rounded-md px-2 py-2 focus:bg-extremecaution transition-all duration-300 bg-caution appearance-none"
                                name="union" required>
                                <option value="">Select your union</option>
                                <option value="unite">Unite</option>
                                <option value="unison">Unison</option>
                                <option value="gmb">GMB</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                                </svg>
                            </div>
                        </div>

                        @if ($workplace)
                            <div>
                                <input
                                    class="w-full border-white placeholder:text-black/75 border text-black rounded-md px-2 py-2 focus:bg-extremecaution transition-all duration-300 bg-caution"
                                    type="text" placeholder="Workplace" name="workplace" required />
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <button type="submit"
                class="border-white border rounded-md px-4 py-2 hover:bg-caution hover:text-extremedanger bg-caution text-extremecaution transition-all duration-300">
                <span>Sign Up</span>
            </button>
        </div>
    </form>

</div>
