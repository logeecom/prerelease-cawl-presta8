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
     * @typedef MultiselectDropdownComponentModel
     *
     * @property {Option[]} options
     * @property {string?} name
     * @property {string[]?} values
     * @property {string?} placeholder
     * @property {string?} selectedText
     * @property {(values: string[]) => void} onChange
     * @property {boolean?} updateTextOnChange
     * @property {boolean?} useAny
     * @property {string?} className
     * */

    /**
     * Multiselect dropdown component.
     *
     * @param {MultiselectDropdownComponentModel} params
     * @returns {HTMLElement}
     * @constructor
     */
    const MultiselectDropdownComponent = ({
        options,
        name = '',
        values = [],
        placeholder,
        selectedText,
        onChange,
        updateTextOnChange = true,
        useAny = true,
        className = ''
    }) => {
        const { elementGenerator: generator, translationService } = OnlinePaymentsFE;

        options.forEach((option) => {
            option.label = translationService.translate(option.label);
        });

        const handleDisplayedItems = (fireChange = true) => {
            hiddenInput.value = OnlinePaymentsFE.sanitize(selectedItems.map((item) => OnlinePaymentsFE.sanitize(item.value).join(',')));
            if (useAny) {
                const anyItem = list.querySelector('.opt--any');
                if (selectedItems.length > 0) {
                    anyItem?.classList.remove('ops--selected');
                } else {
                    anyItem.classList.toggle('ops--selected');

                    list.querySelectorAll(':not(.opt--any)').forEach((listItem) => {
                        listItem.classList.remove('ops--selected');
                        if (anyItem.classList.contains('ops--selected')) {
                            listItem.classList.add('ops--disabled');
                        } else {
                            listItem.classList.remove('ops--disabled');
                        }
                    });
                }
            }

            let textToDisplay;
            if (selectedItems.length > 2) {
                textToDisplay = translationService.translate(selectedText, [selectedItems.length]);
            } else {
                textToDisplay =
                    selectedItems.map((item) => item.label).join(', ') || translationService.translate(placeholder);
            }

            updateTextOnChange && (selectButton.firstElementChild.innerHTML = OnlinePaymentsFE.sanitize(textToDisplay));
            fireChange && onChange?.(selectedItems.map((item) => OnlinePaymentsFE.sanitize(item.value)));
        };

        const createListItem = (additionalClass, label, htmlKey) => {
            const item = generator.createElement('li', `opp-dropdown-list-item ${additionalClass}`, label, htmlKey, [
                generator.createElement('input', 'opp-checkbox', '', { type: 'checkbox' })
            ]);
            list.append(item);
            return item;
        };

        const renderOption = (option) => {
            const listItem = createListItem(values?.includes(option.value) ? 'ops--selected' : '', option.label, null);

            selectedItems.forEach((item) => {
                if (option.value === item.value) {
                    listItem.classList.add('ops--selected');
                }
            });

            listItem.addEventListener('click', () => {
                listItem.classList.toggle('ops--selected');
                listItem.childNodes[0].checked = listItem.classList.contains('ops--selected');
                if (!selectedItems.includes(option)) {
                    selectedItems.push(option);
                } else {
                    const index = selectedItems.indexOf(option);
                    selectedItems.splice(index, 1);
                }

                handleDisplayedItems();
            });
        };

        let selectedItems = options.filter((option) => values?.includes(option.value));

        const hiddenInput = generator.createElement('input', 'opp-hidden-input', '', {
            type: 'hidden',
            name,
            value: values?.join(',') || ''
        });
        const wrapper = generator.createElement('div', 'op-multiselect-dropdown' + (className ? ' ' + className : ''));
        const selectButton = generator.createElement(
            'button',
            'opp-dropdown-button opp-field-component',
            '',
            {
                type: 'button'
            },
            [generator.createElement('span', selectedItems ? 'ops--selected' : '', placeholder)]
        );

        const list = generator.createElement('ul', 'opp-dropdown-list');
        if (useAny) {
            const anyItem = createListItem('opt--any' + (!values?.length ? ' ops--selected' : ''), 'general.any', null);

            anyItem.addEventListener('click', () => {
                selectedItems = [];
                anyItem.childNodes[0].checked = anyItem.classList.contains('ops--selected');

                handleDisplayedItems();
            });
        }

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

        wrapper.append(hiddenInput, selectButton, list);

        values?.length && handleDisplayedItems(false);

        return wrapper;
    };

    OnlinePaymentsFE.components.MultiselectDropdown = {
        /**
         * @param {MultiselectDropdownComponentModel} config
         * @returns {HTMLElement}
         */
        create: (config) => MultiselectDropdownComponent(config)
    };
})();
