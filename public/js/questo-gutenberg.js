(function () {
    var __ = wp.i18n.__;
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    const iconEl = el('svg', {width: 20, height: 20, viewBox: "0 0 768 768", class: 'dashicon'},
        el('path', {d: "M366.3,537c-20.2,0-37,6.4-50.5,19.1c-13.5,12.8-20.2,29-20.2,48.9c0,19.9,6.7,36.1,20.2,48.9   c13.5,12.7,30.3,19.1,50.5,19.1c20.2,0,37-6.4,50.5-19.1c13.5-12.7,20.2-29,20.2-48.9c0-19.8-6.7-36.1-20.2-48.9   C403.3,543.4,386.5,537,366.3,537z"}),
        el('path', {d: "M527.8,133c-33.7-25.3-81.2-38-142.6-38c-61.6,0-114.6,17.1-151.1,42c-44.6,30.4-44,98.9-44.6,148h131.2V109.8   L503.3,248L322,385.4c-3.2,29-3.2,55.2-3.2,83.6h118c0.5-18.7,3.4-34.1,8.7-46.2c5.2-12.2,14.6-24,28.1-35.6l47.5-39.4   c20.1-17.4,34.7-34.5,43.8-51.2c9.1-16.7,13.6-35.2,13.6-55.6C578.4,194.2,561.5,158.3,527.8,133z"})
    );

    registerBlockType('questo/placeholder', {
        title: 'Adquesto',
        icon: iconEl,
        category: 'embed',

        edit: function () {
            return el(
                'div',
                {className: 'questo-should-be-inserted-here'}
            );
        },

        save: function () {
            return el(
                'div',
                {className: 'questo-should-be-inserted-here'}
            );
        },
    });
})();
