//TinyMCE editor
(function () {
    var pluginName = 'questoButton';
    var contentClass = 'questo-should-be-inserted-here';
    tinymce.PluginManager.add(pluginName, function (editor, url) {
        // Add Button to Visual Editor Toolbar
        editor.addButton(pluginName, {
            title: (tinymce.settings.language == 'pl' ? 'Wstaw Adquesto' : 'Insert Adquesto'),
            cmd: pluginName,
            image: url + '/../img/tinymce-icon.png'
        });

        // Add Command when Button Clicked
        editor.addCommand(pluginName, function () {
            tinymce.activeEditor.dom.remove(tinymce.activeEditor.dom.select('div.' + contentClass));
            editor.execCommand('mceReplaceContent', false, '<div class="' + contentClass + '"></div>');
            tinymce.activeEditor.dom.setAttrib(tinymce.activeEditor.dom.select('div.' + contentClass), 'contenteditable', 'false');
        });

        // if tinymce is loaded we remove editable from contentClass div
        var disableQuestoHerePlaceholder = function () {
            if (typeof (tinymce.activeEditor.dom) === 'undefined') {
                setTimeout(function () {
                    disableQuestoHerePlaceholder();
                }, 250);
            } else {
                tinymce.activeEditor.dom.setAttrib(tinymce.activeEditor.dom.select('div.' + contentClass), 'contenteditable', 'false');
            }
        };
        disableQuestoHerePlaceholder();

        jQuery('.wp-switch-editor.switch-tmce').on('click', function () {
            setTimeout(function () {
                disableQuestoHerePlaceholder();
            }, 250);
        });
    });
})();
