<v-tinymce {{ $attributes }}></v-tinymce>

<button class="flex gap-[4px] px-[8px] py-[6px] rounded-[4px] bg-blue-50 text-[14px] text-blue-600 transition-all hover:bg-blue-100">Magic AI</button>

@pushOnce('scripts')
    <!--
        TODO (@devansh-webkul): Only this portion is pending; it just needs to be integrated using the Vite bundler. Currently,
        there is an issue with relative paths in the plugins. I intend to address this task at the end.
    -->
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.6.2/tinymce.min.js"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    ></script>

    <script type="module">
        app.component('v-tinymce', {
            props: ['selector', 'field'],

            data() {
                return {
                    currentSkin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
                    currentContentCSS: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                };
            },

            mounted() {
                this.init();

                this.$emitter.on('change-theme', (theme) => {
                    tinymce.activeEditor.destroy();
                  
                    this.currentSkin = (theme === 'dark') ? 'oxide-dark' : 'oxide';
                    this.currentContentCSS = (theme === 'dark') ? 'dark' : 'default';

                    this.init();
                });
            },

            methods: {
                init() {
                    let tinymceSelf = this;

                    // TODO (@devansh-webkul): Need to refactor this full method.
                    let tinyMCEHelper = {
                        initTinyMCE: function(extraConfiguration) {
                            let self = this;

                            let config = {
                                relative_urls: false,
                                menubar: false,
                                remove_script_host: false,
                                document_base_url: '{{ asset('/') }}',
                                uploadRoute: '{{ route('admin.tinymce.upload') }}',
                                csrfToken: '{{ csrf_token() }}',
                                ...extraConfiguration,
                                skin: tinymceSelf.currentSkin,
                                content_css: tinymceSelf.currentContentCSS,
                            };

                            tinymce.init({
                                ...config,

                                file_picker_callback: function(cb, value, meta) {
                                    self.filePickerCallback(config, cb, value, meta);
                                },

                                images_upload_handler: function(blobInfo, success, failure, progress) {
                                    self.uploadImageHandler(config, blobInfo, success, failure, progress);
                                },
                            });
                        },

                        filePickerCallback: function(config, cb, value, meta) {
                            let input = document.createElement('input');
                            input.setAttribute('type', 'file');
                            input.setAttribute('accept', 'image/*');

                            input.onchange = function() {
                                let file = this.files[0];

                                let reader = new FileReader();
                                reader.readAsDataURL(file);
                                reader.onload = function() {
                                    let id = 'blobid' + new Date().getTime();
                                    let blobCache = tinymce.get().editorUpload.blobCache;
                                    let base64 = reader.result.split(',')[1];
                                    let blobInfo = blobCache.create(id, file, base64);
                                    blobCache.add(blobInfo);
                                    cb(blobInfo.blobUri(), {
                                        title: file.name
                                    });
                                };
                            };
                            input.click();
                        },

                        uploadImageHandler: function(config, blobInfo, success, failure, progress) {
                            let xhr, formData;

                            xhr = new XMLHttpRequest();

                            xhr.withCredentials = false;

                            xhr.open('POST', config.uploadRoute);

                            xhr.upload.onprogress = function(e) {
                                progress((e.loaded / e.total) * 100);
                            };

                            xhr.onload = function() {
                                let json;

                                if (xhr.status === 403) {
                                    failure("@lang('admin::app.error.tinymce.http-error')", {
                                        remove: true
                                    });
                                    return;
                                }

                                if (xhr.status < 200 || xhr.status >= 300) {
                                    failure("@lang('admin::app.error.tinymce.http-error')");
                                    return;
                                }

                                json = JSON.parse(xhr.responseText);

                                if (!json || typeof json.location != 'string') {
                                    failure("@lang('admin::app.error.tinymce.invalid-json')" + xhr.responseText);
                                    return;
                                }

                                success(json.location);
                            };

                            xhr.onerror = function() {
                                failure("@lang('admin::app.error.tinymce.upload-failed')");
                            };

                            formData = new FormData();
                            formData.append('_token', config.csrfToken);
                            formData.append('file', blobInfo.blob(), blobInfo.filename());

                            xhr.send(formData);
                        },
                    };

                    tinyMCEHelper.initTinyMCE({
                        selector: this.selector,
                        plugins: 'image media wordcount save fullscreen code table lists link',
                        toolbar1: 'customInsertButton | formatselect | bold italic strikethrough forecolor backcolor alignleft aligncenter alignright alignjustify | link hr |numlist bullist outdent indent  | removeformat | code | table',
                        image_advtab: true,
                        directionality : "{{ core()->getCurrentLocale()->direction }}",

                        setup: editor => {
                            editor.ui.registry.addIcon('magic', '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"> <g clip-path="url(#clip0_3148_2242)"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12.1484 9.31989L9.31995 12.1483L19.9265 22.7549L22.755 19.9265L12.1484 9.31989ZM12.1484 10.7341L10.7342 12.1483L13.5626 14.9767L14.9768 13.5625L12.1484 10.7341Z" fill="#2563EB"/> <path d="M11.0877 3.30949L13.5625 4.44748L16.0374 3.30949L14.8994 5.78436L16.0374 8.25924L13.5625 7.12124L11.0877 8.25924L12.2257 5.78436L11.0877 3.30949Z" fill="#2563EB"/> <path d="M2.39219 2.39217L5.78438 3.95197L9.17656 2.39217L7.61677 5.78436L9.17656 9.17655L5.78438 7.61676L2.39219 9.17655L3.95198 5.78436L2.39219 2.39217Z" fill="#2563EB"/> <path d="M3.30947 11.0877L5.78434 12.2257L8.25922 11.0877L7.12122 13.5626L8.25922 16.0374L5.78434 14.8994L3.30947 16.0374L4.44746 13.5626L3.30947 11.0877Z" fill="#2563EB"/> </g> <defs> <clipPath id="clip0_3148_2242"> <rect width="24" height="24" fill="white"/> </clipPath> </defs> </svg>');

                            editor.ui.registry.addButton('customInsertButton', {
                                text: 'Magic AI',
                                icon: 'magic',
                                style: 'background-color: #4CAF50; color: white;',
                                onAction: function (_) {
                                    editor.insertContent('&nbsp;<strong>It\'s my button!</strong>&nbsp;');
                                }
                            });

                            editor.on('keyup', () => {
                                this.field.onInput(editor.getContent());
                            });
                        },
                    });
                }
            },
        })
    </script>
@endPushOnce
