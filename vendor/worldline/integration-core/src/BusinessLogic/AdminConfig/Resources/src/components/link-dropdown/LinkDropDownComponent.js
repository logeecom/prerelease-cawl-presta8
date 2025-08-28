if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

if (!window.OnlinePaymentsFE.components) {
    window.OnlinePaymentsFE.components = {};
}

(function () {
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    /**
     * @typedef Option
     *
     * @property {string} label
     * @property {string} link
     */

    /**
     * @typedef LinkDropDownComponentModel
     *
     * @property {Option[]} options
     * @property {string?} name
     * @property {string?} className
     * */

    /**
     * Link dropdown component.
     *
     * @param {LinkDropDownComponentModel} params
     * @returns {HTMLElement}
     * @constructor
     */
    const LinkDropDownComponent = ({
        options,
        name = '',
        className = ''
    }) => {
        const { elementGenerator: generator, translationService } = OnlinePaymentsFE;

        options.forEach((option) => {
            option.label = translationService.translate(option.label);
        });

        const createListItem = (additionalClass, label, htmlKey) => {
            const item = generator.createElement('li', `opp-dropdown-list-item ${additionalClass}`, label, htmlKey, []);
            list.append(item);
            return item;
        };

        const renderOption = (option) => {
            const listItem = createListItem('', option.label, null);

            listItem.addEventListener('click', () => {
                window.open(option.link, '_blank');
            });
        };
        const label = translationService.translate(name);
        const wrapper = generator.createElement('div', 'op-list-dropdown' + (className ? ' ' + className : ''));
        const selectButton = generator.createElement(
            'button',
            'opp-dropdown-button opt--ghost',
            label,
            {
                type: 'button'
            },
            []
        );

        const list = generator.createElement('ul', 'opp-dropdown-list');
        options.forEach(renderOption);

        selectButton.addEventListener('click', (event) => {
            preventDefaults(event);
            list.classList.toggle('ops--show');
            wrapper.classList.toggle('ops--active');
        });

        window.addEventListener('click', (event) => {
            if (!list.contains(event.target) && event.target !== list) {
                list.classList.remove('ops--show');
                wrapper.classList.remove('ops--active');
            }
        });

        wrapper.append(selectButton, list);

        return wrapper;
    };

    OnlinePaymentsFE.components.LinkDropDownComponent = {
        /**
         * @param {LinkDropDownComponentModel} config
         * @returns {HTMLElement}
         */
        create: (config) => LinkDropDownComponent(config)
    };
})();
