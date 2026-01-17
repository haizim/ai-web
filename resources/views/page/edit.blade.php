<x-volt-base title="Edit Halaman">
    @php
        $html = $page->html;
        $html = str_replace("```html", "", $html);
        $html = str_replace("```", "", $html);
        $html = str_replace("`", "", $html);
    @endphp
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* APP STYLES (Editor Interface Only) */
        body {
            overflow: hidden;
            background: #f3f4f6;
        }
        
        /* Layout */
        #app-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        #workspace {
            flex: 1;
            display: flex;
            overflow: hidden;
            position: relative;
        }
        
        /* Panels */
        .panel-source {
            width: 15vw;
            border-right: 1px solid #ddd;
            background: #1e1e1e;
            color: #d4d4d4;
            display: flex;
            flex-direction: column;
            transition: width 0.3s;
        }
        
        .panel-source textarea {
            flex: 1;
            background: transparent;
            border: none;
            color: inherit;
            padding: 1rem;
            font-family: monospace;
            resize: none;
            outline: none;
        }
        
        .panel-preview {
            flex: 1;
            background: #cfd1d6;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: .5rem;
            overflow: hidden;
            position: relative;
        }
        
        .panel-inspector {
            width: 18vw;
            border-left: 1px solid #ddd;
            background: #fff;
            overflow-y: auto;
            transition: width 0.3s;
            z-index: 10;
        }
        
        /* Iframe Viewport */
        #preview-frame {
            background: white;
            transition: width 0.3s ease, height 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border: none;
            height: 100%;
            /* Full height of container */
        }
        
        #preview-frame.desktop {
            width: 100%;
        }
        
        #preview-frame.tablet {
            width: 768px;
        }
        
        #preview-frame.mobile {
            width: 375px;
        }
        
        /* Color Picker Grids */
        .color-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 4px;
            padding: 5px;
        }
        
        .color-swatch {
            width: 100%;
            aspect-ratio: 2/1;
            border-radius: 4px;
            cursor: pointer;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .color-swatch:hover {
            transform: scale(1.1);
        }
        
        /* Utility */
        [x-cloak] {
            display: none !important;
        }
        
        .ui.accordion .title {
            padding: 8px 16px !important;
            background: #f9fafb;
            font-weight: bold;
            font-size: 0.9em;
            border-top: 1px solid #eee;
        }
        
        .ui.accordion .content {
            padding: 16px !important;
        }
    </style>

    <div x-data="editorApp()" x-init="initApp()" @keydown.window="handleShortcuts">

    {!! form()->put()->action(route('page.update', $page->id))->id('form') !!}
        <div id="app-container">

            <div class="ui menu borderless" style="margin: 0; border-radius: 0;">

                <div class="item">
                    <div class="ui icon black buttons small">
                        <button type="button" class="ui button" @click="undo()" :class="{disabled: historyIndex <= 0}"
                            title="Undo (Ctrl+Z)">
                            <i class="undo icon"></i>
                        </button>
                        <button type="button" class="ui black button" @click="redo()"
                            :class="{disabled: historyIndex >= history.length - 1}" title="Redo (Ctrl+Y)">
                            <i class="redo icon"></i>
                        </button>
                    </div>
                </div>

                <div class="item">
                    <input type="text" name="judul" placeholder="Judul" x-model="judul" required>
                </div>

                <div class="item">
                    <div class="ui labeled input">
                        <div class="ui label">
                            {{ config('app.url') }}/p/
                        </div>
                        <input type="text" name="slug" value="{{ $page->slug }}" placeholder="URL(mis: nama-halaman)" :value="judul.replaceAll(/[^a-zA-Z0-9 ]/g, ' ').replaceAll(/\s+/g, ' ').replaceAll(' ', '-').toLowerCase()" required>
                    </div>
                </div>

                <div class="menu right">
                    <div class="item">
                        <div class="ui icon buttons small">
                            <button type="button" class="ui black button" :class="{active: viewport==='mobile'}"
                                @click="viewport='mobile'" title="Mobile (375px)">
                                <i class="mobile alternate icon"></i>
                            </button>
                            <button type="button" class="ui black button" :class="{active: viewport==='tablet'}"
                                @click="viewport='tablet'" title="Tablet (768px)">
                                <i class="tablet alternate icon"></i>
                            </button>
                            <button type="button" class="ui black button" :class="{active: viewport==='desktop'}"
                                @click="viewport='desktop'" title="Full Width">
                                <i class="desktop icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="item">
                        <div class="ui icon buttons small">
                            <button type="button" class="ui black button icon small" @click="showSource = !showSource"
                                :class="{active: showSource}">
                                <i class="code icon"></i>
                            </button>
                            <button type="button" class="ui black button icon small" @click="showInspector = !showInspector"
                                :class="{active: showInspector}">
                                <i class="sliders horizontal icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="item">
                        <div class="ui icon buttons small">
                            <button type="submit" class="ui black button icon small">
                                <i class="save icon"></i>
                            </button>
                            <button type="button" class="ui black button" onclick="generatePreview()" id="generate-preview">
                                <i class="undo icon"></i>
                            </button>
                            {{-- <button type="button" class="ui black button icon small" @click="showInspector = !showInspector"
                                :class="{active: showInspector}">
                                <i class="sliders horizontal icon"></i>
                            </button> --}}
                        </div>
                    </div>
                </div>
            </div>

            <div id="workspace">

                <div class="panel-source" x-show="showSource" x-transition.origin.left style="display: none;">
                    <div class="ui small label top attached" style="background:#333; color:#aaa; border:none;">Raw HTML
                    </div>
                    <textarea x-model="htmlContent" @input.debounce.500ms="syncFromSource()" name="html" id="editor"></textarea>
                </div>

                <div class="panel-preview" @click.self="deselect()">
                    <iframe id="preview-frame" :class="viewport" x-ref="iframe">
                    </iframe>
                </div>

                <div class="panel-inspector" x-show="showInspector" x-transition.origin.right style="display: none;">

                    <div style="overflow-x: hidden; overflow-y: scroll; height: 70vh; border-bottom: 2px solid #999;">
                        <div x-show="!selectedEl" class="ui text center container basic segment"
                            style="margin-top: 50px; opacity: 0.5;">
                            <i class="hand pointer icon" style="font-size: 48px; margin-bottom: 10px; display:block;"></i>
                            <p>Select an element in the preview</p>
                        </div>

                        <div x-show="selectedEl">

                            <div class="ui segment basic"
                                style="padding: 10px; background: #f8f9fa; border-bottom: 1px solid #eee;">
                                <div class="ui breadcrumb small"
                                    style="margin-bottom: 10px; display: block; overflow:hidden; white-space: nowrap; text-overflow: ellipsis;">
                                    <template x-for="(tag, index) in breadcrumbs" :key="index">
                                        <span>
                                            <a class="section" @click="selectParent(index)" x-text="tag"></a>
                                            <span x-show="index !== breadcrumbs.length - 1" class="divider">/</span>
                                        </span>
                                    </template>
                                </div>
                                <div class="ui icon buttons mini fluid">
                                    <button type="button" class="ui button" title="Move Up" @click="moveEl(-1)"><i
                                            class="arrow up icon"></i></button>
                                    <button type="button" class="ui button" title="Move Down" @click="moveEl(1)"><i
                                            class="arrow down icon"></i></button>
                                    <button type="button" class="ui button" title="Duplicate" @click="duplicateEl()"><i
                                            class="copy icon"></i></button>
                                    <button type="button" class="ui negative button" title="Delete" @click="deleteEl()"><i
                                            class="trash alternate icon"></i></button>
                                </div>
                            </div>

                            <div class="ui segment basic form mini"
                                style="padding: 10px; margin: 0; border-bottom: 1px solid #eee;">
                                <div class="field">
                                    <label>Raw Classes</label>
                                    <textarea rows="2" x-model="rawClasses" @input="updateRawClasses()"
                                        style="font-family: monospace; font-size: 0.85rem; color: #d63384; resize: vertical;"></textarea>
                                </div>
                            </div>

                            <div class="ui styled accordion fluid" id="inspector-accordion">

                                <!-- <div class="active title"><i class="dropdown icon"></i> Components</div>
                                <div class="active content">
                                    <div class="ui grid two column">
                                        <template x-for="comp in presetComponents">
                                            <div class="column" style="padding: 4px;">
                                                <button type="button" class="ui button tiny fluid basic" @click="insertComponent(comp.html)">
                                                    <i :class="comp.icon + ' icon'"></i> <span x-text="comp.name"></span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div> -->

                                <div class="title"><i class="dropdown icon"></i> Content & Attributes</div>
                                <div class="active content">
                                    <form class="ui form mini">
                                        <template x-for="(node, i) in textNodes" :key="i">
                                            <div class="field">
                                                <label>Text Content</label>
                                                <textarea x-model="node.text" @input="updateTextNode(i, node.text)"
                                                    rows="3"></textarea>
                                            </div>
                                        </template>

                                        <div class="field" x-show="selectedTag === 'IMG'">
                                            <label>Image Source</label>
                                            <input type="text" x-model="attrSrc" @change="updateAttr('src', attrSrc)">
                                        </div>
                                        <div class="field" x-show="selectedTag === 'A'">
                                            <label>Link HREF</label>
                                            <input type="text" x-model="attrHref" @change="updateAttr('href', attrHref)">
                                        </div>
                                    </form>
                                </div>

                                <div class="title"><i class="dropdown icon"></i> Typography</div>
                                <div class="content">
                                    <div class="ui form mini">
                                        <div class="field">
                                            <label>Alignment</label>
                                            <div class="ui icon buttons fluid basic">
                                                <button type="button" class="ui button" @click="toggleClassRegex('text-', 'left')"
                                                    :class="selectedProperties.typography.alignment === 'left' || !selectedProperties.typography.alignment ? 'active' : ''">
                                                    <i class="align left icon"></i>
                                                </button>
                                                <button type="button" class="ui button" @click="toggleClassRegex('text-', 'center')"
                                                    :class="selectedProperties.typography.alignment === 'center' ? 'active' : ''">
                                                    <i class="align center icon"></i>
                                                </button>
                                                <button type="button" class="ui button" @click="toggleClassRegex('text-', 'right')"
                                                    :class="selectedProperties.typography.alignment === 'right' ? 'active' : ''">
                                                    <i class="align right icon"></i>
                                                </button>
                                                <button type="button" class="ui button" @click="toggleClassRegex('text-', 'justify')"
                                                    :class="selectedProperties.typography.alignment === 'justify' ? 'active' : ''">
                                                    <i class="align justify icon"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label>Size</label>
                                            <select @change="toggleClassRegex('text-', $event.target.value)"
                                                x-model="selectedProperties.typography.size">
                                                <option value="">Default</option>
                                                <option value="xs">XS</option>
                                                <option value="sm">SM</option>
                                                <option value="base">Base</option>
                                                <option value="lg">LG</option>
                                                <option value="xl">XL</option>
                                                <option value="2xl">2XL</option>
                                                <option value="4xl">4XL</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Weight</label>
                                            <select @change="toggleClassRegex('font-', $event.target.value)"
                                                x-model="selectedProperties.typography.weight">
                                                <option value="light">Light</option>
                                                <option value="normal">Normal</option>
                                                <option value="medium">Medium</option>
                                                <option value="bold">Bold</option>
                                                <option value="extrabold">Extra Bold</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <div class="field">
                                                <label>Text Color</label>

                                                <!-- Tabs for Normal / Hover -->
                                                <div class="ui pointing secondary menu mini" style="margin-bottom: 10px;">
                                                    <a class="item" :class="{active: textMode === 'normal'}"
                                                        @click="textMode = 'normal'">Normal</a>
                                                    <a class="item" :class="{active: textMode === 'hover'}"
                                                        @click="textMode = 'hover'">Hover</a>
                                                </div>

                                                <!-- NORMAL MODE -->
                                                <div x-show="textMode === 'normal'">
                                                    <div
                                                        style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 8px;">
                                                        <template x-for="f in colors.families">
                                                            <div class="color-swatch"
                                                                :class="['white', 'black'].includes(f) ? `bg-${f}` : `bg-${f}-500`"
                                                                :style="pickerFamilies.text === f ? 'border: 2px solid #333;' : 'border: 1px solid #ddd;'"
                                                                @click="pickerFamilies.text = f" :title="f">
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <div class="color-grid" style="margin-top:5px;">
                                                        <div class="color-swatch text-center content-center text-red-500 border-slate-200"
                                                            @click="removeColor('text')"> X </div>
                                                        <template x-if="!['white', 'black'].includes(pickerFamilies.text)">
                                                            <template x-for="shade in colors.shades">
                                                                <div class="color-swatch text-center content-center" :class="[
                                                                        `bg-${pickerFamilies.text}-${shade}`,
                                                                        (selectedProperties.typography.color.family === pickerFamilies.text && selectedProperties.typography.color.shade === shade) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                    ]"
                                                                    @click="applyColor('text', pickerFamilies.text, shade)">
                                                                    <i x-show="selectedProperties.typography.color.family === pickerFamilies.text && selectedProperties.typography.color.shade === shade"
                                                                        class="check icon"
                                                                        :class="parseInt(shade) > 500 ? 'text-white' : 'text-black'"></i>
                                                                </div>
                                                            </template>
                                                        </template>
                                                        <template x-if="['white', 'black'].includes(pickerFamilies.text)">
                                                            <div class="color-swatch text-gray-500" :class="[
                                                                    `bg-${pickerFamilies.text}`,
                                                                    (selectedProperties.typography.color.family === pickerFamilies.text) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                ]" @click="applyColor('text', pickerFamilies.text, '')"
                                                                style="border: 1px solid #ddd;">
                                                                <i x-show="selectedProperties.typography.color.family === pickerFamilies.text"
                                                                    class="check icon"
                                                                    :class="pickerFamilies.text === 'white' ? 'text-black' : 'text-white'"></i>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <!-- Custom Color Input -->
                                                    <div style="margin-top: 8px; display: flex; align-items: center;">
                                                        <label style="font-size: 11px; margin-right: 8px;">Custom:</label>
                                                        <div class="ui input mini" style="flex: 1;">
                                                            <input type="color"
                                                                style="padding: 0; height: 28px; width: 40px; border: none; background: none;"
                                                                x-model="customColors.text"
                                                                @input="applyArbitraryColor('text', $event.target.value)">
                                                            <input type="text" x-model="customColors.text"
                                                                @change="applyArbitraryColor('text', $event.target.value)"
                                                                placeholder="#..." style="margin-left:5px; flex:1;">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- HOVER MODE -->
                                                <div x-show="textMode === 'hover'">
                                                    <div
                                                        style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 8px;">
                                                        <template x-for="f in colors.families">
                                                            <div class="color-swatch"
                                                                :class="['white', 'black'].includes(f) ? `bg-${f}` : `bg-${f}-500`"
                                                                :style="pickerFamilies.textHover === f ? 'border: 2px solid #333;' : 'border: 1px solid #ddd;'"
                                                                @click="pickerFamilies.textHover = f" :title="f">
                                                            </div>
                                                        </template>
                                                    </div>

                                                    <div class="color-grid" style="margin-top:5px;">
                                                        <div class="color-swatch text-center content-center text-red-500 border-slate-200"
                                                            @click="removeColor('text', 'hover')"> X </div>
                                                        <template
                                                            x-if="!['white', 'black'].includes(pickerFamilies.textHover)">
                                                            <template x-for="shade in colors.shades">
                                                                <div class="color-swatch text-center content-center" :class="[
                                                                        `bg-${pickerFamilies.textHover}-${shade}`,
                                                                        (selectedProperties.typography.color.hover.family === pickerFamilies.textHover && selectedProperties.typography.color.hover.shade === shade) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                    ]"
                                                                    @click="applyColor('text', pickerFamilies.textHover, shade, 'hover')">
                                                                    <i x-show="selectedProperties.typography.color.hover.family === pickerFamilies.textHover && selectedProperties.typography.color.hover.shade === shade"
                                                                        class="check icon"
                                                                        :class="parseInt(shade) > 500 ? 'text-white' : 'text-black'"></i>
                                                                </div>
                                                            </template>
                                                        </template>
                                                        <template
                                                            x-if="['white', 'black'].includes(pickerFamilies.textHover)">
                                                            <div class="color-swatch text-gray-500" :class="[
                                                                    `bg-${pickerFamilies.textHover}`,
                                                                    (selectedProperties.typography.color.hover.family === pickerFamilies.textHover) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                ]"
                                                                @click="applyColor('text', pickerFamilies.textHover, '', 'hover')"
                                                                style="border: 1px solid #ddd;">
                                                                <i x-show="selectedProperties.typography.color.hover.family === pickerFamilies.textHover"
                                                                    class="check icon"
                                                                    :class="pickerFamilies.textHover === 'white' ? 'text-black' : 'text-white'"></i>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <!-- Custom Color Input -->
                                                    <div style="margin-top: 8px; display: flex; align-items: center;">
                                                        <label style="font-size: 11px; margin-right: 8px;">Custom:</label>
                                                        <div class="ui input mini" style="flex: 1;">
                                                            <input type="color"
                                                                style="padding: 0; height: 28px; width: 40px; border: none; background: none;"
                                                                x-model="customColors.textHover"
                                                                @input="applyArbitraryColor('text', $event.target.value, 'hover')">
                                                            <input type="text" x-model="customColors.textHover"
                                                                @change="applyArbitraryColor('text', $event.target.value, 'hover')"
                                                                placeholder="#..." style="margin-left:5px; flex:1;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="title"><i class="dropdown icon"></i> Background</div>
                                <div class="content">
                                    <div class="ui form mini">
                                        <div class="field">
                                            <label>Color</label>
                                            <!-- Tabs for Normal / Hover -->
                                            <div class="ui pointing secondary menu mini" style="margin-bottom: 10px;">
                                                <a class="item" :class="{active: bgMode === 'normal'}"
                                                    @click="bgMode = 'normal'">Normal</a>
                                                <a class="item" :class="{active: bgMode === 'hover'}"
                                                    @click="bgMode = 'hover'">Hover</a>
                                            </div>

                                            <!-- NORMAL MODE -->
                                            <div x-show="bgMode === 'normal'">
                                                <div
                                                    style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 8px;">
                                                    <template x-for="f in colors.families">
                                                        <div class="color-swatch"
                                                            :class="['white', 'black'].includes(f) ? `bg-${f}` : `bg-${f}-500`"
                                                            :style="pickerFamilies.bg === f ? 'border: 2px solid #333;' : 'border: 1px solid #ddd;'"
                                                            @click="pickerFamilies.bg = f" :title="f">
                                                        </div>
                                                    </template>
                                                </div>

                                                <div class="color-grid" style="margin-top:5px;">
                                                    <div class="color-swatch text-center content-center text-red-500 border-slate-200"
                                                        @click="removeColor('bg')"> X </div>
                                                    <template x-if="!['white', 'black'].includes(pickerFamilies.bg)">
                                                        <template x-for="shade in colors.shades">
                                                            <div class="color-swatch text-center content-center" :class="[
                                                                        `bg-${pickerFamilies.bg}-${shade}`,
                                                                        (selectedProperties.background.color.family === pickerFamilies.bg && selectedProperties.background.color.shade === shade) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                    ]" @click="applyColor('bg', pickerFamilies.bg, shade)">
                                                                <i x-show="selectedProperties.background.color.family === pickerFamilies.bg && selectedProperties.background.color.shade === shade"
                                                                    class="check icon"
                                                                    :class="parseInt(shade) > 500 ? 'text-white' : 'text-black'"></i>
                                                            </div>
                                                        </template>
                                                    </template>
                                                    <template x-if="['white', 'black'].includes(pickerFamilies.bg)">
                                                        <div class="color-swatch" :class="[
                                                                    `bg-${pickerFamilies.bg}`,
                                                                    (selectedProperties.background.color.family === pickerFamilies.bg) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                ]" @click="applyColor('bg', pickerFamilies.bg, '')"
                                                            style="border: 1px solid #ddd;">
                                                            <i x-show="selectedProperties.background.color.family === pickerFamilies.bg"
                                                                class="check icon"
                                                                :class="pickerFamilies.bg === 'white' ? 'text-black' : 'text-white'"></i>
                                                        </div>
                                                    </template>
                                                </div>
                                                <!-- Custom Color Input -->
                                                <div style="margin-top: 8px; display: flex; align-items: center;">
                                                    <label style="font-size: 11px; margin-right: 8px;">Custom:</label>
                                                    <div class="ui input mini" style="flex: 1;">
                                                        <input type="color"
                                                            style="padding: 0; height: 28px; width: 40px; border: none; background: none;"
                                                            x-model="customColors.bg"
                                                            @input="applyArbitraryColor('bg', $event.target.value)">
                                                        <input type="text" x-model="customColors.bg"
                                                            @change="applyArbitraryColor('bg', $event.target.value)"
                                                            placeholder="#..." style="margin-left:5px; flex:1;">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- HOVER MODE -->
                                            <div x-show="bgMode === 'hover'">
                                                <div
                                                    style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 8px;">
                                                    <template x-for="f in colors.families">
                                                        <div class="color-swatch"
                                                            :class="['white', 'black'].includes(f) ? `bg-${f}` : `bg-${f}-500`"
                                                            :style="pickerFamilies.bgHover === f ? 'border: 2px solid #333;' : 'border: 1px solid #ddd;'"
                                                            @click="pickerFamilies.bgHover = f" :title="f">
                                                        </div>
                                                    </template>
                                                </div>

                                                <div class="color-grid" style="margin-top:5px;">
                                                    <div class="color-swatch text-center content-center text-red-500 border-slate-200"
                                                        @click="removeColor('bg', 'hover')"> X </div>
                                                    <template x-if="!['white', 'black'].includes(pickerFamilies.bgHover)">
                                                        <template x-for="shade in colors.shades">
                                                            <div class="color-swatch text-center content-center" :class="[
                                                                        `bg-${pickerFamilies.bgHover}-${shade}`,
                                                                        (selectedProperties.background.color.hover.family === pickerFamilies.bgHover && selectedProperties.background.color.hover.shade === shade) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                    ]"
                                                                @click="applyColor('bg', pickerFamilies.bgHover, shade, 'hover')">
                                                                <i x-show="selectedProperties.background.color.hover.family === pickerFamilies.bgHover && selectedProperties.background.color.hover.shade === shade"
                                                                    class="check icon"
                                                                    :class="parseInt(shade) > 500 ? 'text-white' : 'text-black'"></i>
                                                            </div>
                                                        </template>
                                                    </template>
                                                    <template x-if="['white', 'black'].includes(pickerFamilies.bgHover)">
                                                        <div class="color-swatch" :class="[
                                                                    `bg-${pickerFamilies.bgHover}`,
                                                                    (selectedProperties.background.color.hover.family === pickerFamilies.bgHover) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                ]"
                                                            @click="applyColor('bg', pickerFamilies.bgHover, '', 'hover')"
                                                            style="border: 1px solid #ddd;">
                                                            <i x-show="selectedProperties.background.color.hover.family === pickerFamilies.bgHover"
                                                                class="check icon"
                                                                :class="pickerFamilies.bgHover === 'white' ? 'text-black' : 'text-white'"></i>
                                                        </div>
                                                    </template>
                                                </div>
                                                <!-- Custom Color Input -->
                                                <div style="margin-top: 8px; display: flex; align-items: center;">
                                                    <label style="font-size: 11px; margin-right: 8px;">Custom:</label>
                                                    <div class="ui input mini" style="flex: 1;">
                                                        <input type="color"
                                                            style="padding: 0; height: 28px; width: 40px; border: none; background: none;"
                                                            x-model="customColors.bgHover"
                                                            @input="applyArbitraryColor('bg', $event.target.value, 'hover')">
                                                        <input type="text" x-model="customColors.bgHover"
                                                            @change="applyArbitraryColor('bg', $event.target.value, 'hover')"
                                                            placeholder="#..." style="margin-left:5px; flex:1;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label>Image URL</label>
                                            <input type="text" placeholder="https://..."
                                                @change="setBgImage($event.target.value)">
                                        </div>
                                        <div class="field">
                                            <div class="ui buttons tiny fluid">
                                                <button type="button" class="ui button"
                                                    @click="toggleSimpleClass('bg-cover')">Cover</button>
                                                <button type="button" class="ui button"
                                                    @click="toggleSimpleClass('bg-contain')">Contain</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="title"><i class="dropdown icon"></i> Spacing</div>
                                <div class="content">
                                    <div class="ui form mini">
                                        <div class="field">
                                            <label>Padding (p-)</label>
                                            <input type="range" min="0" max="12" step="1"
                                                @input="toggleClassRegex('p-', $event.target.value)">
                                        </div>
                                        <div class="field">
                                            <label>Margin (m-)</label>
                                            <input type="range" min="0" max="12" step="1"
                                                @input="toggleClassRegex('m-', $event.target.value)">
                                        </div>
                                    </div>
                                </div>

                                <div class="title"><i class="dropdown icon"></i> Borders</div>
                                <div class="content">
                                    <div class="ui form mini">
                                        <div class="field">
                                            <label>Width</label>
                                            <div class="ui buttons tiny fluid">
                                                <button type="button" class="ui button" @click="toggleClassRegex('border-', '0')"
                                                    :class="selectedProperties.border.width === '0' ? 'active' : ''">0</button>
                                                <button type="button" class="ui button" @click="toggleClassRegex('border-', '2')"
                                                    :class="selectedProperties.border.width === '2' ? 'active' : ''">2</button>
                                                <button type="button" class="ui button" @click="toggleClassRegex('border-', '4')"
                                                    :class="selectedProperties.border.width === '4' ? 'active' : ''">4</button>
                                                <button type="button" class="ui button" @click="toggleClassRegex('border-', '8')"
                                                    :class="selectedProperties.border.width === '8' ? 'active' : ''">8</button>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label>Radius</label>
                                            <select @change="toggleClassRegex('rounded-', $event.target.value)">
                                                <option value="none">None</option>
                                                <option value="sm">Small</option>
                                                <option value="md">Medium</option>
                                                <option value="lg">Large</option>
                                                <option value="xl">XL</option>
                                                <option value="full">Full</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Color</label>

                                            <!-- Tabs for Normal / Hover -->
                                            <div class="ui pointing secondary menu mini" style="margin-bottom: 10px;">
                                                <a class="item" :class="{active: borderMode === 'normal'}"
                                                    @click="borderMode = 'normal'">Normal</a>
                                                <a class="item" :class="{active: borderMode === 'hover'}"
                                                    @click="borderMode = 'hover'">Hover</a>
                                            </div>

                                            <!-- NORMAL MODE -->
                                            <div x-show="borderMode === 'normal'">
                                                <div
                                                    style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 8px;">
                                                    <template x-for="f in colors.families">
                                                        <div class="color-swatch"
                                                            :class="['white', 'black'].includes(f) ? `bg-${f}` : `bg-${f}-500`"
                                                            :style="pickerFamilies.border === f ? 'border: 2px solid #333;' : 'border: 1px solid #ddd;'"
                                                            @click="pickerFamilies.border = f" :title="f">
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="color-grid" style="margin-top:5px;">
                                                    <div class="color-swatch text-center content-center text-red-500 border-slate-200"
                                                        @click="removeColor('border')"> X </div>
                                                    <template x-if="!['white', 'black'].includes(pickerFamilies.border)">
                                                        <template x-for="shade in colors.shades">
                                                            <div class="color-swatch text-center content-center" :class="[
                                                                        `bg-${pickerFamilies.border}-${shade}`,
                                                                        (selectedProperties.border.color.family === pickerFamilies.border && selectedProperties.border.color.shade === shade) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                    ]"
                                                                @click="applyColor('border', pickerFamilies.border, shade)">
                                                                <i x-show="selectedProperties.border.color.family === pickerFamilies.border && selectedProperties.border.color.shade === shade"
                                                                    class="check icon"
                                                                    :class="parseInt(shade) > 500 ? 'text-white' : 'text-black'"></i>
                                                            </div>
                                                        </template>
                                                    </template>
                                                    <template x-if="['white', 'black'].includes(pickerFamilies.border)">
                                                        <div class="color-swatch" :class="[
                                                                    `bg-${pickerFamilies.border}`,
                                                                    (selectedProperties.border.color.family === pickerFamilies.border) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                ]" @click="applyColor('border', pickerFamilies.border, '')"
                                                            style="border: 1px solid #ddd;">
                                                        </div>
                                                    </template>
                                                </div>
                                                <!-- Custom Color Input -->
                                                <div style="margin-top: 8px; display: flex; align-items: center;">
                                                    <label style="font-size: 11px; margin-right: 8px;">Custom:</label>
                                                    <div class="ui input mini" style="flex: 1;">
                                                        <input type="color"
                                                            style="padding: 0; height: 28px; width: 40px; border: none; background: none;"
                                                            x-model="customColors.border"
                                                            @input="applyArbitraryColor('border', $event.target.value)">
                                                        <input type="text" x-model="customColors.border"
                                                            @change="applyArbitraryColor('border', $event.target.value)"
                                                            placeholder="#..." style="margin-left:5px; flex:1;">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- HOVER MODE -->
                                            <div x-show="borderMode === 'hover'">
                                                <div
                                                    style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 4px; margin-bottom: 8px;">
                                                    <template x-for="f in colors.families">
                                                        <div class="color-swatch"
                                                            :class="['white', 'black'].includes(f) ? `bg-${f}` : `bg-${f}-500`"
                                                            :style="pickerFamilies.borderHover === f ? 'border: 2px solid #333;' : 'border: 1px solid #ddd;'"
                                                            @click="pickerFamilies.borderHover = f" :title="f">
                                                        </div>
                                                    </template>
                                                </div>
                                                <div class="color-grid" style="margin-top:5px;">
                                                    <div class="color-swatch text-center content-center text-red-500 border-slate-200"
                                                        @click="removeColor('border', 'hover')"> X </div>
                                                    <template
                                                        x-if="!['white', 'black'].includes(pickerFamilies.borderHover)">
                                                        <template x-for="shade in colors.shades">
                                                            <div class="color-swatch text-center content-center" :class="[
                                                                        `bg-${pickerFamilies.borderHover}-${shade}`,
                                                                        (selectedProperties.border.color.hover.family === pickerFamilies.borderHover && selectedProperties.border.color.hover.shade === shade) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                    ]"
                                                                @click="applyColor('border', pickerFamilies.borderHover, shade, 'hover')">
                                                                <i x-show="selectedProperties.border.color.hover.family === pickerFamilies.borderHover && selectedProperties.border.color.hover.shade === shade"
                                                                    class="check icon"
                                                                    :class="parseInt(shade) > 500 ? 'text-white' : 'text-black'"></i>
                                                            </div>
                                                        </template>
                                                    </template>
                                                    <template
                                                        x-if="['white', 'black'].includes(pickerFamilies.borderHover)">
                                                        <div class="color-swatch" :class="[
                                                                    `bg-${pickerFamilies.borderHover}`,
                                                                    (selectedProperties.border.color.hover.family === pickerFamilies.borderHover) ? 'ring-2 ring-offset-1 border-transparent' : ''
                                                                ]"
                                                            @click="applyColor('border', pickerFamilies.borderHover, '', 'hover')"
                                                            style="border: 1px solid #ddd;">
                                                        </div>
                                                    </template>
                                                </div>
                                                <!-- Custom Color Input -->
                                                <div style="margin-top: 8px; display: flex; align-items: center;">
                                                    <label style="font-size: 11px; margin-right: 8px;">Custom:</label>
                                                    <div class="ui input mini" style="flex: 1;">
                                                        <input type="color"
                                                            style="padding: 0; height: 28px; width: 40px; border: none; background: none;"
                                                            x-model="customColors.borderHover"
                                                            @input="applyArbitraryColor('border', $event.target.value, 'hover')">
                                                        <input type="text" x-model="customColors.borderHover"
                                                            @change="applyArbitraryColor('border', $event.target.value, 'hover')"
                                                            placeholder="#..." style="margin-left:5px; flex:1;">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="py-2" style="height: 20vh; align-content: end;">
                        <textarea name="" id="command" rows="9" placeholder="Apa yang ingin diubah?" style="width: 100%; padding: .25em;"></textarea>
                        <button type="button" class="ui fluid icon black button" onclick="send_command()">
                            <i class="robot icon"></i> (Ctrl + Enter)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <textarea name="konten" id="konten" style="display: none">{{ $page->konten }}</textarea>
        <textarea name="style" id="style" style="display: none">{{ $page->style }}</textarea>
    {!! form()->close() !!}

    <div class="ui basic modal" id="loading-modal">
        <div class="ui icon header">
            <i class="asterisk loading icon"></i>
        </div>
    </div>

    <script>
        // Init Semantic UI
        $(document).ready(function () {
            $('.ui.accordion').accordion({ exclusive: false });
            $('.ui.dropdown').dropdown();
        });

        const COLOR_FAMILIES = ['white', 'black', 'slate', 'gray', 'zinc', 'neutral', 'stone', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
        const COLOR_SHADES = ['50', '100', '200', '300', '400', '500', '600', '700', '800', '900', '950'];

        const TailwindParser = {
            parseColor(classes, prefix) {
                // Check standard colors: text-red-500
                let regex = new RegExp(`${prefix}-([a-z]+)-(\\d+)`);
                let result = classes.match(regex);
                if (result) return { family: result[1], shade: result[2], isArbitrary: false, value: '' };

                // Check black/white: text-black
                regex = new RegExp(`${prefix}-(white|black)`);
                result = classes.match(regex);
                if (result) return { family: result[1], shade: '', isArbitrary: false, value: '' };

                // Check arbitrary: text-[#123]
                regex = new RegExp(`${prefix}-\\[(.*?)\\]`);
                result = classes.match(regex);
                if (result) return { family: 'custom', shade: '', value: result[1], isArbitrary: true };

                return { family: '', shade: '', isArbitrary: false, value: '' };
            },

            parseRegex(classes, regex) {
                const result = classes.match(regex);
                return result ? result[1] : '';
            }
        };

        let htmldata = '';
        function generatePreview() {
            if (!confirm('Yakin ingin regenerate halaman? Perubahan yang sudah ada akan hilang')) return false;
            $('#loading-modal').modal('show')

            const konten = $('#konten').val();
            const style = $('#style').val();
            const files = $('[name=_files]').val()
            const btn = document.getElementById('generate-preview');
            if (!konten) return alert('konten tidak boleh kosong!');

            btn.disabled = true;
            btn.classList.add('loading')
            fetch('{{ route('api.generate-preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    'konten': konten,
                    'style': style,
                    'files': files
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                html = data
                
                htmldata = data.html
                console.log(htmldata);
                
                let ed = document.getElementById('editor')
                ed.value = htmldata
                ed.dispatchEvent(new Event("input", { bubbles: true }));
                // editorInstance.syncFromSource();
            })
            .catch(error => {
                console.error('Error:', error);
                $.toast({
                    class: 'error',
                    position: 'top center',
                    message: `Terjadi Error, silahkan coba lagi !`
                })
            })
            .finally(() => {
                $('#loading-modal').modal('hide')
                btn.disabled = false;
                btn.classList.remove('loading')
            })
        }

        function send_command() {
            const command = document.getElementById('command').value;
            let ed = document.getElementById('editor')
            // const code = editor.getValue();
            const code = ed.value

            const data_send = {
                'html': code,
                'command': command
            }

            // editor.options.readOnly = true
            $('#loading-modal').modal('show')

            fetch('{{ route('api.edit-page') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data_send)
            })
            .then(response => response.json())
            .then(data => {
                // editor.setValue(data.html);

                htmldata = data.html
                ed.value = htmldata
                ed.dispatchEvent(new Event("input", { bubbles: true }));
            })
            .finally(() => {
                // editor.options.readOnly = false
                $('#loading-modal').modal('hide')
            });
            
            document.getElementById('command').value = '';
            document.getElementById('command').focus();
        }

        document.addEventListener('keydown', (event) => {
            if(event.ctrlKey && event.key == "Enter") {
                send_command()
            }
        });

        let editor = editorApp();
        let editorInstance = null;
        function editorApp() {
            const defaultProps = {
                typography: {
                    alignment: '', size: '', weight: '',
                    color: { family: '', shade: '', isArbitrary: false, value: '', hover: { family: '', shade: '', isArbitrary: false, value: '' } }
                },
                background: {
                    color: { family: '', shade: '', isArbitrary: false, value: '', hover: { family: '', shade: '', isArbitrary: false, value: '' } },
                    image: '', size: ''
                },
                spacing: { padding: '', margin: '' },
                border: {
                    width: '', radius: '',
                    color: { family: '', shade: '', isArbitrary: false, value: '', hover: { family: '', shade: '', isArbitrary: false, value: '' } }
                },
                attributes: { text: '', src: '', href: '' }
            };

            return {
                judul: `{{ ($page->judul) }}`,
                htmlContent: '',
                viewport: 'desktop',
                // ... state
                colors: { families: COLOR_FAMILIES, shades: COLOR_SHADES },
                pickerFamilies: { text: 'slate', textHover: 'slate', bg: 'slate', bgHover: 'slate', border: 'slate', borderHover: 'slate' },
                customColors: { text: '#000000', textHover: '#000000', bg: '#ffffff', bgHover: '#ffffff', border: '#e5e7eb', borderHover: '#e5e7eb' },
                bgMode: 'normal',   // 'normal' | 'hover'
                borderMode: 'normal', // 'normal' | 'hover'
                textMode: 'normal', // 'normal' | 'hover'

                presetComponents: [
                    { name: 'Container', icon: 'box', html: '<div class="container mx-auto p-4 border border-dashed border-gray-300">Container</div>' },
                    { name: 'Grid 2-Col', icon: 'grid layout', html: '<div class="grid grid-cols-2 gap-4"><div class="bg-gray-100 p-4">1</div><div class="bg-gray-100 p-4">2</div></div>' },
                    { name: 'Grid 3-Col', icon: 'grid layout', html: '<div class="grid grid-cols-3 gap-4"><div class="bg-gray-100 p-4">1</div><div class="bg-gray-100 p-4">2</div><div class="bg-gray-100 p-4">3</div></div>' },
                    { name: 'Flex Row', icon: 'arrows alternate horizontal', html: '<div class="flex items-center space-x-4"><div class="bg-blue-100 p-2">Item 1</div><div class="bg-blue-100 p-2">Item 2</div></div>' },
                    { name: 'Button', icon: 'hand pointer', html: '<button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Click Me</button>' },
                    { name: 'Card', icon: 'address card', html: '<div class="max-w-sm rounded overflow-hidden shadow-lg bg-white"><div class="px-6 py-4"><div class="font-bold text-xl mb-2">Card Title</div><p class="text-gray-700 text-base">Some quick example text.</p></div></div>' },
                    { name: 'Image', icon: 'image', html: '<img class="w-full h-48 object-cover" src="https://via.placeholder.com/400x300" alt="Placeholder">' },
                    { name: 'Alert', icon: 'exclamation circle', html: '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"><strong class="font-bold">Alert!</strong><span class="block sm:inline">Something happened.</span></div>' },
                ],

                showSource: false,
                showInspector: true,
                selectedEl: null,
                selectedTag: '',
                breadcrumbs: [],
                textNodes: [],
                rawClasses: '', // Stores the string for the textarea
                attrSrc: '',
                attrHref: '',

                // DATA PROPERTI TERPILIH
                selectedProperties: JSON.parse(JSON.stringify(defaultProps)),

                history: [],
                historyIndex: -1,
                maxHistory: 20,
                iframeDoc: null,

                initApp() {
                    const starter = `{!! $html !!}`;
                    this.htmlContent = starter;
                    this.renderIframe(starter);
                    this.saveHistory();

                    window.addEventListener('app-apply-class-regex', (e) => {
                        this.replaceClassRegex(e.detail.regex, e.detail.newClass);
                    });

                    editorInstance = this;
                },

                // --- COLOR PICKER LOGIC ---
                applyColor(prefix, family, shade, modifier = '') {
                    let className;
                    const fullPrefix = modifier ? `${modifier}:${prefix}` : prefix;

                    if (family === 'black' || family === 'white') {
                        className = `${fullPrefix}-${family}`;
                    } else {
                        className = `${fullPrefix}-${family}-${shade}`;
                    }

                    const colorNames = this.colors.families.join('|');
                    const shadeNames = this.colors.shades.join('|');
                    // Regex to replace existing class for this property+modifier
                    // e.g. hover:bg-... or bg-...
                    const regexPrefix = modifier ? `${modifier}:${prefix}` : prefix;
                    const regexStr = `${regexPrefix}-(?:(?:${colorNames})-(?:${shadeNames})|white|black|\\[.*?\\])`;

                    this.replaceClassRegex(regexStr, className);

                    // Reset custom color input
                    if (modifier) {
                        this.customColors[prefix + 'Hover'] = '#000000';
                    } else {
                        this.customColors[prefix] = '#000000';
                    }
                },

                applyArbitraryColor(prefix, value, modifier = '') {
                    if (!value) return;
                    const fullPrefix = modifier ? `${modifier}:${prefix}` : prefix;
                    const className = `${fullPrefix}-[${value}]`;

                    const colorNames = this.colors.families.join('|');
                    const shadeNames = this.colors.shades.join('|');
                    const regexPrefix = modifier ? `${modifier}:${prefix}` : prefix;
                    const regexStr = `${regexPrefix}-(?:(?:${colorNames})-(?:${shadeNames})|white|black|\\[.*?\\])`;

                    this.replaceClassRegex(regexStr, className);
                },

                insertComponent(html) {
                    if (!this.selectedEl) {
                        // If nothing selected, append to body (or maybe show alert)
                        // For now let's just append to the end of iframe body content
                        if (this.iframeDoc) {
                            this.iframeDoc.body.insertAdjacentHTML('beforeend', html);
                            this.syncFromPreview();
                        }
                        return;
                    }

                    // Insert as child of selected element
                    this.selectedEl.insertAdjacentHTML('beforeend', html);
                    this.syncFromPreview();
                },

                removeColor(prefix, modifier = '') {
                    const colorNames = this.colors.families.join('|');
                    const shadeNames = this.colors.shades.join('|');
                    const regexPrefix = modifier ? `${modifier}:${prefix}` : prefix;
                    const regexStr = `${regexPrefix}-(?:(?:${colorNames})-(?:${shadeNames})|white|black)`;
                    this.replaceClassRegex(regexStr, '');
                },


                // --- Iframe Rendering Logic ---

                renderIframe(content) {
                    const frame = this.$refs.iframe;
                    this.iframeDoc = frame.contentDocument || frame.contentWindow.document;

                    // Write full document into Iframe including Tailwind and Styles
                    this.iframeDoc.open();
                    this.iframeDoc.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <link href="https://fonts.googleapis.com/icon?family=Material+Icons"rel="stylesheet">
                            <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined"rel="stylesheet">
                            <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round"rel="stylesheet">
                            <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp"rel="stylesheet">
                            <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Two+Tone"rel="stylesheet">
                            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
                            <script src="https://cdn.tailwindcss.com"><\/script>
                            <style>
                                /* Selection Outline INJECTED into Iframe */
                                .editor-selected { outline: 2px dashed #2185d0 !important; outline-offset: -2px; cursor: pointer; }
                                .editor-hovered { outline: 1px dashed #fab005 !important; cursor: pointer; }
                                body { min-height: 100vh; background: transparent; }
                            </style>
                        </head>
                        <body>${content}</body>
                        </html>
                    `);
                    this.iframeDoc.close();

                    // Re-attach listeners after iframe load
                    frame.onload = () => {
                        this.iframeDoc = frame.contentDocument || frame.contentWindow.document;
                        this.attachIframeListeners();
                    };
                },

                attachIframeListeners() {
                    if (!this.iframeDoc) return;

                    this.iframeDoc.body.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        // Call back to parent Alpine scope
                        this.selectElement(e.target);
                    });

                    this.iframeDoc.body.addEventListener('mouseover', (e) => {
                        if (e.target === this.iframeDoc.body) return;
                        e.target.classList.add('editor-hovered');
                    });

                    this.iframeDoc.body.addEventListener('mouseout', (e) => {
                        e.target.classList.remove('editor-hovered');
                    });

                    // Click background to deselect
                    this.iframeDoc.addEventListener('click', (e) => {
                        if (e.target === this.iframeDoc.documentElement) this.deselect();
                    });
                },

                // --- Sync Logic ---

                syncFromSource() {
                    // Update Iframe BODY content only, to keep head/scripts intact
                    if (this.iframeDoc && this.iframeDoc.body) {
                        console.log('htmlcontent', this.htmlContent);
                        
                        this.iframeDoc.body.innerHTML = this.htmlContent;
                        this.deselect();
                        this.saveHistory();
                    } else {
                        console.log('tidak ada iframe & iframe body');
                        
                    }
                },

                syncFromPreview() {
                    // Read Iframe BODY
                    if (!this.iframeDoc) return;

                    // Clean classes
                    if (this.selectedEl) this.selectedEl.classList.remove('editor-selected');
                    const clone = this.iframeDoc.body.cloneNode(true);

                    // Remove helpers
                    clone.querySelectorAll('.editor-selected').forEach(el => el.classList.remove('editor-selected'));
                    clone.querySelectorAll('.editor-hovered').forEach(el => el.classList.remove('editor-hovered'));

                    // Remove injected scripts if any (Tailwind usually in head, but safety check)
                    clone.querySelectorAll('script').forEach(el => el.remove());

                    this.htmlContent = clone.innerHTML.trim();

                    if (this.selectedEl) {
                        this.selectedEl.classList.add('editor-selected');
                        // Update rawClasses in case manipulating classes changed something
                        this.rawClasses = this.getCleanClasses(this.selectedEl);
                        this.updateSelectedProperties(this.selectedEl);
                    }
                    this.saveHistory();
                },

                // --- History ---
                saveHistory() {
                    if (this.historyIndex < this.history.length - 1) {
                        this.history = this.history.slice(0, this.historyIndex + 1);
                    }
                    this.history.push(this.htmlContent);
                    if (this.history.length > this.maxHistory) this.history.shift();
                    else this.historyIndex++;
                },

                undo() {
                    if (this.historyIndex > 0) {
                        this.historyIndex--;
                        this.htmlContent = this.history[this.historyIndex];
                        // Write to Iframe body
                        if (this.iframeDoc) this.iframeDoc.body.innerHTML = this.htmlContent;
                        this.deselect();
                    }
                },

                redo() {
                    if (this.historyIndex < this.history.length - 1) {
                        this.historyIndex++;
                        this.htmlContent = this.history[this.historyIndex];
                        if (this.iframeDoc) this.iframeDoc.body.innerHTML = this.htmlContent;
                        this.deselect();
                    }
                },

                handleShortcuts(e) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 'z') { e.preventDefault(); this.undo(); }
                    if ((e.ctrlKey || e.metaKey) && e.key === 'y') { e.preventDefault(); this.redo(); }
                },

                // --- Selection & Manipulation (Works on Iframe Nodes) ---

                getCleanClasses(el) {
                    // Returns class list without internal editor classes
                    return Array.from(el.classList)
                        .filter(c => c !== 'editor-selected' && c !== 'editor-hovered')
                        .join(' ');
                },

                selectElement(el) {
                    if (this.selectedEl) this.selectedEl.classList.remove('editor-selected');
                    if (el === this.iframeDoc.body) return; // Don't select body

                    this.selectedEl = el;
                    this.selectedEl.classList.add('editor-selected');
                    this.selectedTag = el.tagName;
                    this.rawClasses = this.getCleanClasses(el); // Populate Textarea

                    // Breadcrumbs
                    this.breadcrumbs = [];
                    let curr = el;
                    while (curr && curr !== this.iframeDoc.body) {
                        this.breadcrumbs.unshift(curr.tagName.toLowerCase() + (curr.id ? '#' + curr.id : ''));
                        curr = curr.parentElement;
                    }

                    // Content
                    this.textNodes = [];
                    el.childNodes.forEach(node => {
                        if (node.nodeType === 3 && node.nodeValue.trim() !== '') {
                            this.textNodes.push({ node: node, text: node.nodeValue.trim() });
                        }
                    });

                    this.attrSrc = el.getAttribute('src') || '';
                    this.attrHref = el.getAttribute('href') || '';

                    this.updateSelectedProperties(el);
                },

                selectParent(index) {
                    let curr = this.selectedEl;
                    const depth = this.breadcrumbs.length - 1 - index;
                    for (let i = 0; i < depth; i++) {
                        if (curr.parentElement !== this.iframeDoc.body) curr = curr.parentElement;
                    }
                    this.selectElement(curr);
                },

                deselect() {
                    if (this.selectedEl) this.selectedEl.classList.remove('editor-selected');
                    this.selectedEl = null;
                    this.breadcrumbs = [];
                    this.rawClasses = '';
                    this.selectedProperties = JSON.parse(JSON.stringify(defaultProps));
                },

                // --- PROPERTY PARSER ---
                updateSelectedProperties(el) {
                    if (!el) {
                        this.selectedProperties = JSON.parse(JSON.stringify(defaultProps));
                        return;
                    }

                    const classes = Array.from(el.classList).join(' ');

                    this.selectedProperties = {
                        typography: {
                            alignment: TailwindParser.parseRegex(classes, /text-(left|center|right|justify)/),
                            size: TailwindParser.parseRegex(classes, /text-(xs|sm|base|lg|xl|2xl|3xl|4xl)/),
                            weight: TailwindParser.parseRegex(classes, /font-(light|normal|medium|bold|extrabold)/),
                            color: {
                                ...TailwindParser.parseColor(classes, 'text'),
                                hover: TailwindParser.parseColor(classes, 'hover:text')
                            }
                        },
                        background: {
                            color: {
                                ...TailwindParser.parseColor(classes, 'bg'),
                                hover: TailwindParser.parseColor(classes, 'hover:bg')
                            },
                            image: (() => {
                                const m = classes.match(/bg-\[url\('?(.*?)'?\)\]/);
                                return m ? m[1] : '';
                            })(),
                            size: TailwindParser.parseRegex(classes, /bg-(cover|contain)/)
                        },
                        spacing: {
                            padding: TailwindParser.parseRegex(classes, /p-(\d+)/),
                            margin: TailwindParser.parseRegex(classes, /m-(\d+)/)
                        },
                        border: {
                            width: TailwindParser.parseRegex(classes, /border-(\d+)/),
                            radius: TailwindParser.parseRegex(classes, /rounded-(none|sm|md|lg|xl|full)/),
                            color: {
                                ...TailwindParser.parseColor(classes, 'border'),
                                hover: TailwindParser.parseColor(classes, 'hover:border')
                            }
                        },
                        attributes: {
                            text: Array.from(el.childNodes)
                                .filter(n => n.nodeType === 3 && n.nodeValue.trim() !== '')
                                .map(n => n.nodeValue.trim()).join(' '),
                            src: el.getAttribute('src') || '',
                            href: el.getAttribute('href') || ''
                        }
                    };

                    if (this.selectedProperties.typography.color.family) this.pickerFamilies.text = this.selectedProperties.typography.color.family;
                    if (this.selectedProperties.typography.color.hover && this.selectedProperties.typography.color.hover.family) {
                        this.pickerFamilies.textHover = this.selectedProperties.typography.color.hover.family;
                    }

                    if (this.selectedProperties.background.color.family) this.pickerFamilies.bg = this.selectedProperties.background.color.family;
                    if (this.selectedProperties.background.color.hover && this.selectedProperties.background.color.hover.family) {
                        this.pickerFamilies.bgHover = this.selectedProperties.background.color.hover.family;
                    }

                    if (this.selectedProperties.border.color.family) this.pickerFamilies.border = this.selectedProperties.border.color.family;
                    if (this.selectedProperties.border.color.hover && this.selectedProperties.border.color.hover.family) {
                        this.pickerFamilies.borderHover = this.selectedProperties.border.color.hover.family;
                    }

                    // Sync Custom Colors
                    if (this.selectedProperties.typography.color.isArbitrary) this.customColors.text = this.selectedProperties.typography.color.value;
                    if (this.selectedProperties.typography.color.hover && this.selectedProperties.typography.color.hover.isArbitrary) {
                        this.customColors.textHover = this.selectedProperties.typography.color.hover.value;
                    }

                    if (this.selectedProperties.background.color.isArbitrary) this.customColors.bg = this.selectedProperties.background.color.value;
                    if (this.selectedProperties.background.color.hover && this.selectedProperties.background.color.hover.isArbitrary) {
                        this.customColors.bgHover = this.selectedProperties.background.color.hover.value;
                    }

                    if (this.selectedProperties.border.color.isArbitrary) this.customColors.border = this.selectedProperties.border.color.value;
                    if (this.selectedProperties.border.color.hover && this.selectedProperties.border.color.hover.isArbitrary) {
                        this.customColors.borderHover = this.selectedProperties.border.color.hover.value;
                    }

                    console.log('Selected Properties:', this.selectedProperties);
                },

                // --- RAW CLASS UPDATE ---
                updateRawClasses() {
                    if (!this.selectedEl) return;

                    // 1. Reset classname to the user input
                    this.selectedEl.className = this.rawClasses;

                    // 2. Re-apply internal selection class so we don't lose the outline
                    this.selectedEl.classList.add('editor-selected');

                    // 3. Sync
                    this.syncFromPreview();
                },

                updateTextNode(index, newVal) {
                    if (this.selectedEl && this.textNodes[index]) {
                        this.textNodes[index].node.nodeValue = newVal;
                        this.syncFromPreview();
                    }
                },

                updateAttr(name, val) {
                    if (this.selectedEl) {
                        this.selectedEl.setAttribute(name, val);
                        this.syncFromPreview();
                    }
                },

                setBgImage(val) {
                    if (!this.selectedEl) return;
                    const cls = Array.from(this.selectedEl.classList);
                    cls.forEach(c => { if (c.startsWith('bg-[url')) this.selectedEl.classList.remove(c); });
                    if (val) this.selectedEl.classList.add(`bg-[url('${val}')]`);
                    this.syncFromPreview();
                },

                moveEl(dir) {
                    if (!this.selectedEl) return;
                    const p = this.selectedEl.parentElement;
                    if (dir === -1 && this.selectedEl.previousElementSibling) {
                        p.insertBefore(this.selectedEl, this.selectedEl.previousElementSibling);
                        this.syncFromPreview();
                    } else if (dir === 1 && this.selectedEl.nextElementSibling) {
                        p.insertBefore(this.selectedEl.nextElementSibling, this.selectedEl);
                        this.syncFromPreview();
                    }
                },

                duplicateEl() {
                    if (!this.selectedEl) return;
                    const clone = this.selectedEl.cloneNode(true);
                    clone.classList.remove('editor-selected');
                    this.selectedEl.parentElement.insertBefore(clone, this.selectedEl.nextElementSibling);
                    this.syncFromPreview();
                },

                deleteEl() {
                    if (!this.selectedEl) return;
                    this.selectedEl.remove();
                    this.deselect();
                    this.syncFromPreview();
                },

                replaceClassRegex(regexStr, newClass) {
                    if (!this.selectedEl) return;
                    const regex = new RegExp(regexStr);
                    const list = Array.from(this.selectedEl.classList);
                    list.forEach(cls => { if (regex.test(cls)) this.selectedEl.classList.remove(cls); });
                    if (newClass) this.selectedEl.classList.add(newClass);
                    this.syncFromPreview();
                },

                toggleClassRegex(prefix, val) {
                    if (!this.selectedEl) return;
                    let regexStr = '';
                    if (prefix === 'text-') {
                        if (['left', 'center', 'right', 'justify'].includes(val)) regexStr = 'text-(left|center|right|justify)';
                        else regexStr = 'text-(xs|sm|base|lg|xl|2xl|3xl|4xl)';
                    } else if (prefix === 'font-') regexStr = 'font-(light|normal|medium|bold|extrabold)';
                    else if (prefix === 'p-') regexStr = 'p-[0-9]+';
                    else if (prefix === 'm-') regexStr = 'm-[0-9]+';
                    else if (prefix === 'rounded-') { regexStr = 'rounded-(none|sm|md|lg|xl|full)'; if (val === 'none') val = 'rounded-none'; }
                    else if (prefix === 'border-') regexStr = 'border-(0|2|4|8)';
                    const newClass = (prefix === 'rounded-' || prefix === 'border-') ? (val.startsWith(prefix) ? val : prefix + val) : prefix + val;
                    this.replaceClassRegex(regexStr, newClass);
                },

                toggleSimpleClass(cls) {
                    if (!this.selectedEl) return;
                    if (this.selectedEl.classList.contains(cls)) this.selectedEl.classList.remove(cls);
                    else this.selectedEl.classList.add(cls);
                    this.syncFromPreview();
                },

                exportHtml() {
                    this.syncFromPreview();
                    const exportTemplate = `<!DOCTYPE html><html><head><script src="https://cdn.tailwindcss.com"><\/script></head><body>${this.htmlContent}</body></html>`;
                    const blob = new Blob([exportTemplate], { type: "text/html" });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = "tailwind-export.html";
                    a.click();
                }
            }
        }
    </script>
</div>
</x-volt-base>