jQuery(function ($) {
    function setValuesInDetailSettingsTextarea() {
        $('input[name="questo_display_settings"]').val(getJsonDetailSettings());
    }

    function getJsonDetailSettings() {
        var values = {
            "postTypes": $('.questo-display-settings input[data-name="post_type"]:checked')
                .map(function () {
                    return $(this).val();
                })
                .get(),
            "categories": $('.questo-display-settings input[data-name="category"]:checked')
                .map(function () {
                    return parseInt($(this).val());
                })
                .get()
        };

        return JSON.stringify(values);
    }

    function setDetailSettings() {
        try {
            var values = JSON.parse($('input[name="questo_display_settings"]').val());
            $.each(values, function (name, checkboxes) {
                $.each(checkboxes, function (i, v) {
                    var dataName = '';
                    switch (name) {
                        case "postTypes":
                            dataName = 'post_type';
                            break;
                        case "categories":
                            dataName = 'category';
                            break;
                    }
                    $('input[type="checkbox"][data-name="' + dataName + '"][value="' + v + '"]').attr('checked', 'checked');
                });
            });
        } catch (error) {
            setValuesInDetailSettingsTextarea()
        }
    }

    $('.questo-display-settings input[type="checkbox"]').on('change', function () {
        setValuesInDetailSettingsTextarea()
    });

    setDetailSettings();
});