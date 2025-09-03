if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

(function () {
    /**
     * @typedef Option
     * @property {string?} label
     * @property {any} value
     */

    /**
     * @typedef {Object.<string, *>} ElementProps
     * @property {string?} name
     * @property {any?} value
     * @property {string?} className
     * @property {string?} placeholder
     * @property {(value: any) => any?} onChange
     * @property {string?} label
     * @property {string?} description
     * @property {string?} error
     */

    /**
     * @typedef {ElementProps} FormField
     * @property {'text' | 'number' | 'radio' |'dropdown' | 'checkbox' | 'file' | 'multiselect' | 'button' |
     *     'buttonLink'} type
     */

    const translationService = OnlinePaymentsFE.translationService;

    /**
     * Prevents default event handling.
     * @param {Event} e
     */
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    /**
     * Creates a generic HTML node element and assigns provided class and inner text.
     *
     * @param {keyof HTMLElementTagNameMap} type Represents the name of the tag
     * @param {string?} className CSS class
     * @param {string?} innerHTMLKey Inner text translation key.
     * @param {Record<string, any>?} properties An object of additional properties.
     * @param {HTMLElement[]?} children
     * @param {boolean} allowHtml
     * @returns {HTMLElement}
     */
    const createElement = (type, className, innerHTMLKey, properties, children, allowHtml = false) => {
        const child = document.createElement(type);
        className && child.classList.add(...className.trim().split(' '));
        if (innerHTMLKey) {
            let params = innerHTMLKey.split('|');

            if (allowHtml) {
                child.innerHTML = OnlinePaymentsFE.sanitize(translationService.translate(params[0], params.slice(1)));
            } else {
                child.innerText = OnlinePaymentsFE.sanitize(translationService.translate(params[0], params.slice(1)));
            }
        }

        if (properties) {
            if (properties.dataset) {
                Object.assign(child.dataset, properties.dataset);
                delete properties.dataset;
            }

            Object.assign(child, properties);
            if (properties.onChange) {
                child.addEventListener('change', properties.onChange, false);
            }

            if (properties.onClick) {
                child.addEventListener('click', properties.onClick, false);
            }
        }

        if (children) {
            child.append(...children);
        }

        return child;
    };

    /**
     * Creates an element out of provided HTML markup.
     *
     * @param {string} html
     * @returns {HTMLElement}
     */
    const createElementFromHTML = (html) => {
        const element = document.createElement('div');
        element.innerHTML = html;

        return element.firstElementChild;
    };

    /**
     * Creates a button.
     *
     * @param {{ label?: string, type?: 'primary' | 'secondary' | 'ghost', size?: 'small' | 'medium', className?:
     *     string, [key: string]: any, onClick?: () => void}} props
     * @return {HTMLButtonElement}
     */
    const createButton = ({ type, size, className, onClick, label, ...properties }) => {
        const cssClass = ['op-button'];
        type && cssClass.push('opt--' + type);
        size && cssClass.push('opm--' + size);
        className && cssClass.push(className);

        const button = createElement('button', cssClass.join(' '), '', { type: 'button', ...properties }, [
            createElement('span', '', label)
        ]);

        onClick &&
        button.addEventListener(
            'click',
            (event) => {
                preventDefaults(event);
                onClick();
            },
            false
        );

        return button;
    };

    /**
     * Creates a link that looks like a button.
     *
     * @param {{text?: string, className?: string, href: string, useDownload?: boolean, downloadFile?: string}} props
     * @return {HTMLLinkElement}
     */
    const createButtonLink = ({ text, className = '', href, useDownload, downloadFile }) => {
        const link = createElement('a', className, `<span>${text}</span>`, { href: href, target: '_blank' });
        if (useDownload) {
            link.setAttribute('download', downloadFile);
        }

        return link;
    };

    /**
     * Creates an input field wrapper around the provided input element.
     *
     * @param {HTMLElement} input The input element.
     * @param {string?} label Label translation key.
     * @param {string?} description Description translation key.
     * @param {string?} error Error translation key.
     * @return {HTMLDivElement}
     */
    const createFieldWrapper = (input, label, description, error) => {
        const field = createElement('div', 'op-field-wrapper');
        let titleWrapper;
        if (label) {
            titleWrapper = createElement('div', 'op-field-title-wrapper');
            titleWrapper.appendChild(createElement('h3', 'opp-field-title', label));
            field.appendChild(titleWrapper);
        }

        if (description && translationService.translate(description) !== '') {
            if (titleWrapper !== undefined) {
                titleWrapper.appendChild(createHint('', description, 'right',''));
                field.appendChild(titleWrapper);
            } else {
                field.appendChild(createHint('', description, 'right',''));
            }
        }

        field.appendChild(input);

        if (error) {
            field.appendChild(createElement('span', 'opp-input-error', error));
        }

        return field;
    };

    /**
     * Creates store switcher.
     *
     * @param {{value: string, label: string}[]} options
     * @param {string?} name
     * @param {string?} title
     * @param {string?} value
     * @param {(value: string) => Promise<boolean>?} onBeforeChange
     * @param {(value: string) => void?} onChange
     * @param {boolean?} updateTextOnChange
     * @return {HTMLDivElement}
     */
    const createStoreSwitcher = (options, name, value, onBeforeChange, onChange, updateTextOnChange = true) => {
        const hiddenInput = createElement('input', 'opp-hidden-input', '', { type: 'hidden', name, value });
        const wrapper = createElement('div', 'op-store-selector');
        const list = createElement('ul', 'opp-dropdown-list');
        const switchButton = createElement('button', 'opp-dropdown-button opp-field-component', '', {
            type: 'button'
        });
        const selectedItem = options.find((option) => option.value === value) || options[0];
        const buttonSpan = createElement('span', '', selectedItem.label);

        switchButton.append(buttonSpan);
        const listItems = [];

        const handleOnOptionChange = (listItem, storeId) => {
            hiddenInput.value = storeId;
            updateTextOnChange && (switchButton.firstElementChild.innerHTML = OnlinePaymentsFE.sanitize(listItem.innerText));
            list.classList.remove('ops--show');

            listItems.forEach((li) => li.classList.remove('ops--selected'));
            listItem.classList.add('ops--selected');
            onChange && onChange(storeId);
        };

        options.forEach((option) => {
            const listItem = createElement('li', 'opp-store', option.label);
            listItems.push(listItem);
            list.append(listItem);
            if (option.value === selectedItem.value) {
                listItem.classList.add('ops--selected');
            }

            listItem.addEventListener('click', () => {
                if (option.value === hiddenInput.value) {
                    list.classList.remove('ops--show');
                    return;
                }

                if (!onBeforeChange) {
                    handleOnOptionChange(listItem, OnlinePaymentsFE.sanitize(option.value));
                } else {
                    onBeforeChange(OnlinePaymentsFE.sanitize(option.value)).then((resume) => {
                        if (resume) {
                            handleOnOptionChange(listItem, OnlinePaymentsFE.sanitize(option.value));
                        } else {
                            list.classList.remove('ops--show');
                        }
                    });
                }
            });
        });

        switchButton.addEventListener('click', (event) => {
            preventDefaults(event);
            list.classList.toggle('ops--show');
        });

        document.documentElement.addEventListener('click', () => {
            list.classList.remove('ops--show');
        });

        wrapper.append(hiddenInput, switchButton, list);

        return wrapper;
    };

    /**
     * @param {string} mode
     * @return {HTMLDivElement}
     */
    const createModeElement = (mode) => {
        const modeDiv = createElement('div', 'op-mode');
        const indicator = createElement('span', 'op-icon');

        if (mode === 'test') {
            indicator.classList.add('op-sandbox');
        } else {
            indicator.classList.add('op-live');
        }

        const modeText = createElement('p', 'op-mode-text', 'general.mode.' + mode);
        modeDiv.appendChild(indicator);
        modeDiv.appendChild(modeText);

        return modeDiv;
    };

    /**
     * Creates dropdown wrapper around the provided dropdown element.
     *
     * @param {ElementProps & DropdownComponentModel} props The properties.
     * @return {HTMLDivElement}
     */
    const createDropdownField = ({ label, description, error, ...dropdownProps }) => {
        return createFieldWrapper(OnlinePaymentsFE.components.Dropdown.create(dropdownProps), label, description, error);
    };

    /**
     * Creates dropdown wrapper around the provided dropdown element.
     *
     * @param {(ElementProps & MultiselectDropdownComponentModel)} props The properties.
     * @return {HTMLDivElement}
     */
    const createMultiselectDropdownField = ({ label, description, error, ...dropdownProps }) => {
        return createFieldWrapper(
            OnlinePaymentsFE.components.MultiselectDropdown.create(dropdownProps),
            label,
            description,
            error
        );
    };

    /**
     * Creates dropdown with links.
     *
     * @param options
     * @param name
     * @param className
     *
     * @returns {HTMLElement}
     */
    const createLinkDropdownField = ({options, name, className}) => {
        return OnlinePaymentsFE.components.LinkDropDownComponent.create({options, name, className});
    }

    /**
     * Creates a password input field.
     *
     * @param {ElementProps} props The properties.
     * @return {HTMLElement}
     */
    const createPasswordField = ({ className = '', label, description, error, onChange, ...rest }) => {
        const wrapper = createElement('div', `op-password ${className}`);
        const input = createElement('input', 'opp-field-component', '', { type: 'password', ...rest });
        onChange && input.addEventListener('change', (event) => onChange(OnlinePaymentsFE.sanitize(event.currentTarget?.value)));

        wrapper.append(input);

        return createFieldWrapper(wrapper, label, description, error);
    };

    /**
     * Creates a text input field.
     *
     * @param {ElementProps & { type?: 'text' | 'number' }} props The properties.
     * @return {HTMLElement}
     */
    const createTextField = ({ className = '', label, description, error, onChange, ...rest }) => {
        /** @type HTMLInputElement */
        const input = createElement('input', `opp-field-component ${className}`, '', { type: 'text', ...rest });
        onChange && input.addEventListener('change', (event) => onChange(OnlinePaymentsFE.sanitize(event.currentTarget?.value)));

        return createFieldWrapper(input, label, description, error);
    };

    /**
     * Creates a number input field.
     *
     * @param {ElementProps} props The properties.
     * @return {HTMLElement}
     */
    const createNumberField = ({ onChange, ...rest }) => {
        const handleChange = (value) => onChange(value === '' ? null : Number(value));

        return createTextField({ type: 'number', step: '1', onChange: handleChange, ...rest });
    };

    /**
     * Creates a radio group field.
     *
     * @param {ElementProps} props The properties.
     * @return {HTMLElement}
     */
    const createRadioGroupField = ({ name, value, className, options, label, description, error, onChange }) => {
        const wrapper = createElement('div', 'op-radio-input-group');
        options.forEach((option) => {
            const label = createElement('label', 'op-radio-input');
            const props = { type: 'radio', value: option.value, name };
            if (value === option.value) {
                props.checked = 'checked';
            }

            label.append(createElement('input', className, '', props), createElement('span', '', option.label));
            wrapper.append(label);
            onChange && label.addEventListener('click', () => onChange(OnlinePaymentsFE.sanitize(option.value)));
        });

        return createFieldWrapper(wrapper, label, description, error);
    };

    /**
     * Creates a checkbox field.
     *
     * @param {ElementProps} props The properties.
     * @return {HTMLElement}
     */
    const createCheckboxField = ({ className = '', label, description, error, onChange, value, ...rest }) => {
        /** @type HTMLInputElement */
        const checkbox = createElement('input', 'opp-toggle-input', '', { type: 'checkbox', checked: value, ...rest });
        onChange && checkbox.addEventListener('change', () => onChange(checkbox.checked));

        const field = createElement('div', 'op-field-wrapper opt--checkbox', '', null, [
            createElement('h3', 'opp-field-title', label, null, [
                createElement('label', 'op-toggle', '', null, [checkbox, createElement('span', 'opp-toggle-round')])
            ])
        ]);

        if (description) {
            field.appendChild(createElement('span', 'opp-field-subtitle', description));
        }

        if (error) {
            field.appendChild(createElement('span', 'opp-input-error', error));
        }

        return field;
    };

    /**
     * Creates a button field.
     *
     * @param {ElementProps & { onClick?: () => void , buttonType?: string, buttonSize?: string,
     *     buttonLabel?: string}} props The properties.
     * @return {HTMLElement}
     */
    const createButtonField = ({ label, description, buttonType, buttonSize, buttonLabel, onClick, error }) => {
        const button = createButton({
            type: buttonType,
            size: buttonSize,
            className: '',
            label: translationService.translate(buttonLabel),
            onClick: onClick
        });

        return createFieldWrapper(button, label, description, error);
    };

    /**
     * Creates a field with a link that looks like a button.
     *
     * @param {ElementProps & {text: string, href: string}} props
     */
    const createButtonLinkField = ({ label, text, description, href, error }) => {
        const buttonLink = createButtonLink({
            text: translationService.translate(text),
            className: '',
            href: href
        });

        return createFieldWrapper(buttonLink, label, description, error);
    };

    /**
     * Creates a flash message.
     *
     * @param {string|string[]} messageKey
     * @param {'error' | 'warning' | 'success'} status
     * @param {number?} clearAfter Time in ms to remove alert message.
     * @return {HTMLElement}
     */
    const createFlashMessage = (messageKey, status, clearAfter) => {
        const hideHandler = () => {
            wrapper.remove();
        };
        const wrapper = createElement('div', `op-alert opt--${status}`);
        let messageBlock;
        if (Array.isArray(messageKey)) {
            const [titleKey, descriptionKey] = messageKey;
            messageBlock = createElement('div', 'opp-alert-title', '', null, [
                createElement('span', 'opp-message', '', null, [
                    createElement('span', 'opp-message-title', titleKey),
                    createElement('span', 'opp-message-description', descriptionKey)
                ])
            ]);
        } else {
            messageBlock = createElement('span', 'opp-alert-title', messageKey);
        }

        const button = createButton({ onClick: hideHandler });

        if (clearAfter) {
            setTimeout(hideHandler, clearAfter);
        }

        wrapper.append(messageBlock, button);

        return wrapper;
    };

    /**
     * Adds a label with a hint.
     *
     * @param {string} label
     * @param {string} hint
     * @param {'left' | 'right' | 'top' | 'bottom'} position
     * @param {string?} className
     * @returns HTMLElement
     */
    const createHint = (label, hint, position, className = '') => {
        const element = createElement('div', `op-hint ${className}`, label);
        const questionMark = createElement('span', '', '?');
        element.appendChild(questionMark);
        element.append(createElement('span', 'opp-tooltip opt--' + position, hint));
        element.addEventListener('mouseenter', () => {
            element.classList.add('ops--active');
        });
        element.addEventListener('mouseout', () => {
            element.classList.remove('ops--active');
        });

        return element;
    };

    /**
     * Creates a toaster message.
     *
     * @param {string} label
     * @param {'success' | 'error' | 'info'} type
     * @param {number} timeout Clear timeout in ms.
     * @returns {HTMLElement}
     */
    const createToaster = (label, type, timeout = 5000) => {
        let elements = [];

        if (type === 'error') {
            elements.push(createElement('span', 'op-cross-icon'));
        }

        elements.push(createElement('span', 'opp-toaster-title', label));
        let button = createElement('button', 'op-button', '', null, [createElement('span')]);
        button.addEventListener('click', () => toaster.remove());
        elements.push(button);

        const toaster = createElement('div', 'op-toaster op-' + type, '', null,
            elements
        );

        button.addEventListener('click', () => toaster.remove());

        setTimeout(() => toaster.remove(), timeout);

        return toaster;
    };

    /**
     *
     * @param {ElementProps & { supportedMimeTypes: string[] }} props
     * @returns {HTMLDivElement}
     */
    const createFileUploadField = ({
        name,
        placeholder,
        label,
        description,
        error,
        value,
        onChange,
        supportedMimeTypes
    }) => {
        const setActive = (e) => {
            preventDefaults(e);
            wrapper.classList.add('ops--active');
        };

        const setInactive = (e) => {
            preventDefaults(e);
            wrapper.classList.remove('ops--active');
        };

        const previewFile = (file, img) => {
            let reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onloadend = function () {
                img.src = reader.result;
            };
        };

        const handleDrop = (e) => {
            const file = e.dataTransfer?.files?.[0] || null;
            if (file) {
                handleFileChange(file);
            }
        };

        const handleFileChange = (file) => {
            if (!supportedMimeTypes.includes(file.type)) {
                OnlinePaymentsFE.validationService.setError(wrapper, 'validation.invalidImageType');
                return;
            }

            if (file.size > 10000000) {
                OnlinePaymentsFE.validationService.setError(wrapper, 'validation.invalidImageSize');
                return;
            }

            onChange(file);
            OnlinePaymentsFE.validationService.removeError(wrapper);
            textElem.classList.remove('ops--empty');
            textElem.innerText = OnlinePaymentsFE.sanitize(file.name);
            const img = createElement('img');
            textElem.prepend(img);
            previewFile(file, img);
        };

        const wrapper = createElement('div', 'op-file-drop-zone opp-field-component');
        const labelElem = createElement('label', 'opp-input-file-label');
        const textElem = createElement('span', 'opp-file-label' + (!value ? ' ops--empty' : ''), placeholder);
        if (value) {
            textElem.prepend(createElement('img', '', '', { src: value }));
        }

        const fileUpload = createElement('input', 'opp-input-file', '', {
            type: 'file',
            accept: 'image/*',
            name: name
        });
        fileUpload.addEventListener('change', () => handleFileChange(fileUpload.files?.[0]));

        labelElem.append(textElem, fileUpload);
        wrapper.append(labelElem);

        ['dragenter', 'dragover'].forEach((eventName) => {
            wrapper.addEventListener(eventName, setActive, false);
        });
        ['dragleave', 'drop'].forEach((eventName) => {
            wrapper.addEventListener(eventName, setInactive, false);
        });
        wrapper.addEventListener('drop', handleDrop, false);

        return createFieldWrapper(wrapper, label, description, error);
    };

    /**
     * Adds a form footer with save and cancel buttons.
     *
     * @param {() => void} onSave
     * @param {() => void} onCancel
     * @param {string} cancelLabel
     * @param {HTMLButtonElement[]} extraButtons
     * @returns HTMLElement
     */
    const createFormFooter = (onSave, onCancel, cancelLabel = 'general.cancel', extraButtons = []) => {
        return createElement('div', 'op-form-footer', '', null, [
            createElement('span', 'opp-changes-count', 'general.unsavedChanges'),
            createElement('div', 'opp-actions', '', null, [
                ...extraButtons,
                createButton({
                    type: 'secondary',
                    className: 'opp-cancel',
                    label: cancelLabel,
                    onClick: onCancel,
                    disabled: true
                }),
                createButton({
                    type: 'primary',
                    className: 'opp-save',
                    label: 'general.saveChanges',
                    onClick: onSave,
                    disabled: true
                })
            ])
        ]);
    };

    /**
     * Creates form fields based on the fields configurations.
     *
     * @param {FormField[]} fields
     */
    const createFormFields = (fields) => {
        /** @type HTMLElement[] */
        const result = [];
        fields.forEach(({ type, ...rest }) => {
            switch (type) {
                case 'text':
                    result.push(createTextField({ ...rest, className: 'op-text-input' }));
                    break;
                case 'number':
                    result.push(createNumberField({ ...rest, className: 'op-text-input' }));
                    break;
                case 'dropdown':
                    result.push(createDropdownField(rest));
                    break;
                case 'multiselect':
                    result.push(createMultiselectDropdownField(rest));
                    break;
                case 'radio':
                    result.push(createRadioGroupField(rest));
                    break;
                case 'checkbox':
                    result.push(createCheckboxField(rest));
                    break;
                case 'file':
                    result.push(createFileUploadField(rest));
                    break;
                case 'button':
                    result.push(createButtonField(rest));
                    break;
                case 'buttonLink':
                    result.push(createButtonLinkField(rest));
                    break;
            }

            rest.className && result[result.length - 1].classList.add(...rest.className.trim().split(' '));
        });

        return result;
    };

    /**
     * Creates a main header item.
     *
     * @param {string} brand
     * @param {LinkDropDownComponentModel} linkDropDown
     * @param {string} page
     *
     * @returns {HTMLElement}
     */
    const createHeaderItem = (brand, linkDropDown, page) => {
        return OnlinePaymentsFE.components.Header.create({brand, linkDropDown, page})
    };

    OnlinePaymentsFE.elementGenerator = {
        createElement,
        createElementFromHTML,
        createButton,
        createHint,
        createDropdownField,
        createMultiselectDropdownField,
        createLinkDropdownField,
        createPasswordField,
        createTextField,
        createNumberField,
        createRadioGroupField,
        createFlashMessage,
        createStoreSwitcher,
        createModeElement,
        createFileUploadField,
        createButtonField,
        createButtonLinkField,
        createFormFields,
        createFormFooter,
        createToaster,
        createHeaderItem,
        createFieldWrapper
    };
})();
