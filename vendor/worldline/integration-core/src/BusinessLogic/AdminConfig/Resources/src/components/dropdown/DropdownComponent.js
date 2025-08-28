if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

if (!window.OnlinePaymentsFE.components) {
    window.OnlinePaymentsFE.components = {};
}

(function () {
    /**
     * @typedef DropdownComponentModel
     *
     * @property {Option[]} options
     * @property {string?} name
     * @property {string?} value
     * @property {string?} placeholder
     * @property {(value: string) => void?} onChange
     * @property {boolean?} updateTextOnChange
     * @property {boolean?} searchable
     */

    /**
     * Single-select dropdown component.
     *
     * @param {DropdownComponentModel} props
     *
     * @constructor
     */
    const DropdownComponent = ({
        options,
        name,
        value = '',
        placeholder,
        onChange,
        updateTextOnChange = true,
        searchable = false
    }) => {
        const { elementGenerator: generator, translationService } = OnlinePaymentsFE;
        const filterItems = (text) => {
            const filteredItems = text
                ? options.filter((option) => option.label.toLowerCase().includes(text.toLowerCase()))
                : options;

            if (filteredItems.length === 0) {
                selectButton.classList.add('ops--no-results');
            } else {
                selectButton.classList.remove('ops--no-results');
            }

            renderOptions(filteredItems);
        };

        const renderOptions = (options) => {
            list.innerHTML = '';
            options.forEach((option) => {
                const listItem = generator.createElement(
                    'li',
                    'opp-dropdown-list-item' + (option === selectedItem ? ' ops--selected' : ''),
                    option.label
                );
                list.append(listItem);

                listItem.addEventListener('click', () => {
                    hiddenInput.value = OnlinePaymentsFE.sanitize(option.value);
                    updateTextOnChange && (buttonSpan.innerHTML = OnlinePaymentsFE.sanitize(translationService.translate(option.label)));
                    list.classList.remove('ops--show');
                    list.childNodes.forEach((node) => node.classList.remove('ops--selected'));
                    listItem.classList.add('ops--selected');
                    wrapper.classList.remove('ops--active');
                    buttonSpan.classList.add('ops--selected');
                    selectButton.classList.remove('ops--search-active');
                    onChange && onChange(OnlinePaymentsFE.sanitize(option.value));
                });
            });
        };

        const hiddenInput = generator.createElement('input', 'opp-hidden-input', '', { type: 'hidden', name, value });
        const wrapper = generator.createElement('div', 'op-single-select-dropdown');

        const selectButton = generator.createElement('button', 'opp-dropdown-button opp-field-component', '', {
            type: 'button'
        });
        const selectedItem = options.find((option) => option.value === value);
        const buttonSpan = generator.createElement(
            'span',
            selectedItem ? 'ops--selected' : '',
            selectedItem ? selectedItem.label : placeholder
        );
        selectButton.append(buttonSpan);

        const searchInput = generator.createElement('input', 'op-text-input', '', {
            type: 'text',
            placeholder: translationService.translate('general.search')
        });
        searchInput.addEventListener('input', (event) => filterItems(event.currentTarget?.value || ''));
        if (searchable) {
            selectButton.append(searchInput);
        }

        const list = generator.createElement('ul', 'opp-dropdown-list');
        renderOptions(options);

        selectButton.addEventListener('click', () => {
            list.classList.toggle('ops--show');
            wrapper.classList.toggle('ops--active');
            if (searchable) {
                selectButton.classList.toggle('ops--search-active');
                if (selectButton.classList.contains('ops--search-active')) {
                    searchInput.focus();
                    searchInput.value = '';
                    filterItems('');
                }
            }
        });

        document.documentElement.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target) && event.target !== wrapper) {
                list.classList.remove('ops--show');
                wrapper.classList.remove('ops--active');
                selectButton.classList.remove('ops--search-active');
            }
        });

        wrapper.append(hiddenInput, selectButton, list);

        return wrapper;
    };

    OnlinePaymentsFE.components.Dropdown = {
        /**
         * @param {DropdownComponentModel} config
         * @returns {HTMLElement}
         */
        create: (config) => DropdownComponent(config)
    };
})();
