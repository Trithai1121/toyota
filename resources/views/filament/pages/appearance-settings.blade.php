<x-filament::page>
    @php
        $colorFields = [
            'color_page_bg' => 'Màu nền toàn trang',
            'color_text' => 'Màu chữ chính',
            'color_footer_bg' => 'Màu nền Footer',
            'color_navbar_bg' => 'Màu nền Navbar (khi không dùng chung Footer)',
            'color_nav_button_border' => 'Màu viền nút menu',
            'color_nav_button_hover' => 'Màu Hover và Active của nút menu',
        ];
    @endphp

    <div
        x-data="{
            colors: {
                page: $wire.entangle('settings.color_page_bg').live,
                gradientEnd: $wire.entangle('settings.page_bg_gradient_end').live,
                text: $wire.entangle('settings.color_text').live,
                footer: $wire.entangle('settings.color_footer_bg').live,
                navbar: $wire.entangle('settings.color_navbar_bg').live,
                border: $wire.entangle('settings.color_nav_button_border').live,
                hover: $wire.entangle('settings.color_nav_button_hover').live,
            },
            followFooter: $wire.entangle('settings.navbar_follow_footer').live,
            gradientEnabled: $wire.entangle('settings.page_bg_gradient_enabled').live,
            gradientDirection: $wire.entangle('settings.page_bg_gradient_direction').live,
            get navbarColor() { return this.followFooter ? this.colors.footer : this.colors.navbar },
            contrast(hex) {
                const channels = [1, 3, 5].map(index => parseInt(hex.slice(index, index + 2), 16) / 255);
                const linear = channels.map(value => value <= 0.04045 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4));
                return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2] > 0.179 ? '#111111' : '#FFFFFF';
            },
            get navbarText() { return this.contrast(this.navbarColor) },
            get footerText() { return this.contrast(this.colors.footer) },
            get hoverText() { return this.contrast(this.colors.hover) },
        }"
        class="space-y-6"
    >
        <form wire:submit="save" class="space-y-6">
            <x-filament::section heading="Màu sắc website" description="Chọn màu bằng bảng màu hoặc nhập mã HEX. Các thay đổi được xem trước bên dưới.">
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach($colorFields as $key => $label)
                        <div class="space-y-2">
                            <label for="{{ $key }}-hex" class="text-sm font-medium text-gray-950 dark:text-white">{{ $label }}</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    aria-label="{{ $label }}"
                                    x-model="colors.{{ match($key) { 'color_page_bg' => 'page', 'color_text' => 'text', 'color_footer_bg' => 'footer', 'color_navbar_bg' => 'navbar', 'color_nav_button_border' => 'border', default => 'hover' } }}"
                                    :disabled="{{ $key === 'color_navbar_bg' ? 'followFooter' : 'false' }}"
                                    class="h-11 w-14 cursor-pointer rounded border border-gray-300 p-1 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                <input
                                    id="{{ $key }}-hex"
                                    type="text"
                                    maxlength="7"
                                    spellcheck="false"
                                    x-model="colors.{{ match($key) { 'color_page_bg' => 'page', 'color_text' => 'text', 'color_footer_bg' => 'footer', 'color_navbar_bg' => 'navbar', 'color_nav_button_border' => 'border', default => 'hover' } }}"
                                    :disabled="{{ $key === 'color_navbar_bg' ? 'followFooter' : 'false' }}"
                                    class="w-full rounded-lg border-gray-300 font-mono text-sm uppercase shadow-sm disabled:bg-gray-100"
                                    placeholder="#FFFFFF"
                                >
                            </div>
                            @error('settings.'.$key)<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>

                <label class="mt-6 flex min-h-11 items-center gap-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                    <input type="checkbox" x-model="followFooter" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                    Navbar dùng cùng màu với Footer
                </label>
                @error('settings.navbar_follow_footer')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
            </x-filament::section>

            <x-filament::section heading="Gradient nền toàn trang" description="Màu nền trang là điểm bắt đầu; chọn màu kết thúc, hướng chuyển màu và bật/tắt gradient tại đây.">
                <label class="flex min-h-11 items-center gap-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                    <input type="checkbox" wire:model.live="settings.page_bg_gradient_enabled" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                    Bật gradient nền website
                </label>
                @error('settings.page_bg_gradient_enabled')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror

                <div class="mt-5 grid gap-5 md:grid-cols-2" x-show="gradientEnabled" x-transition>
                    <div class="space-y-2">
                        <label for="page-gradient-end" class="text-sm font-medium text-gray-950 dark:text-white">Màu kết thúc gradient</label>
                        <div class="flex items-center gap-3">
                            <input type="color" aria-label="Màu kết thúc gradient" x-model="gradientEnd" class="h-11 w-14 cursor-pointer rounded border border-gray-300 p-1">
                            <input id="page-gradient-end" type="text" maxlength="7" spellcheck="false" x-model="gradientEnd" class="w-full rounded-lg border-gray-300 font-mono text-sm uppercase shadow-sm" placeholder="#F3E8EA">
                        </div>
                        @error('settings.page_bg_gradient_end')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="space-y-2">
                        <label for="page-gradient-direction" class="text-sm font-medium text-gray-950 dark:text-white">Hướng chuyển màu</label>
                        <select id="page-gradient-direction" wire:model.live="settings.page_bg_gradient_direction" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="90deg">Ngang</option>
                            <option value="135deg">Chéo</option>
                            <option value="180deg">Dọc</option>
                        </select>
                        @error('settings.page_bg_gradient_direction')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section heading="Xem trước trực tiếp">
                <div class="overflow-hidden rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2 p-4" :style="`background:${navbarColor};color:${navbarText}`">
                        <span class="mr-3 font-bold">Toyota Kon Tum</span>
                        <a href="#preview" class="rounded-md border px-4 py-2 text-sm" :style="`border-color:${colors.border};color:inherit`">Trang chủ</a>
                        <a href="#preview" class="rounded-md border px-4 py-2 text-sm" :style="`background:${colors.hover};border-color:${colors.hover};color:${hoverText}`">Xe Toyota</a>
                        <a href="#preview" class="rounded-md border px-4 py-2 text-sm" :style="`border-color:${colors.border};color:inherit`">Liên hệ</a>
                    </div>
                    <div class="p-6" :style="{ background: gradientEnabled ? 'linear-gradient(' + gradientDirection + ',' + colors.page + ',' + gradientEnd + ')' : colors.page, color: colors.text }">
                        <div class="rounded-lg border border-black/10 p-4" style="background:#F5F6F7">
                            <strong class="block">Khối nội dung mẫu</strong>
                            <span class="text-sm">Thẻ nội dung và biểu mẫu dùng nền trắng hoặc xám rất nhạt.</span>
                        </div>
                    </div>
                    <div class="p-4" :style="`background:${colors.footer};color:${footerText}`">
                        <strong>Footer mẫu</strong><p class="mt-1 text-sm opacity-80">Thông tin liên hệ và liên kết đại lý</p>
                    </div>
                </div>
            </x-filament::section>

            <div class="flex flex-wrap justify-end gap-3">
                <x-filament::button type="button" color="gray" wire:click="resetDefaults" wire:confirm="Khôi phục toàn bộ màu giao diện về mặc định?">Khôi phục mặc định</x-filament::button>
                <x-filament::button type="submit">Lưu thay đổi</x-filament::button>
            </div>
        </form>
    </div>
</x-filament::page>
