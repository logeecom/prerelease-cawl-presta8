if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

if (!window.OnlinePaymentsFE.components) {
    window.OnlinePaymentsFE.components = {};
}

(function() {
    const preventDefaults = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    /**
     * @typedef VaultTitle
     *
     * @property {string} language
     * @property {string} translation
     */

    /**
     * @typedef AdditionalData
     *
     * @property {VaultTitle[]?} vaultTitleCollection
     * @property {boolean?} instantPayment
     * @property {string?} recurrenceType
     * @property {string?} signatureType
     * @property {int?} sessionTimeout
     * @property {string?} paymentProductId
     * @property {string?} paymentOption
     * @property {string?} logo
     * @property {boolean?} enableGroupCards
     */

    /**
     * @typedef SlidingModalModel
     *
     * @property {string} paymentProductId
     * @property {string} name
     * @property {boolean} enabled
     * @property {string} translations
     * @property {string[]} integrationTypes
     * @property {string} paymentGroup
     * @property {string} templateName
     * @property {string} logo
     * @property {AdditionalData} additionalData
     */

    /**
     * @typedef Language
     *
     * @property {string} code
     * @property {string} logo
     */

    /**
     * @param {SlidingModalModel} configuration
     * @param {string} saveMethodConfigurationUrl
     * @param {Language[]} languages
     *
     * @returns {*|HTMLElement}
     * @constructor
     */
    const SlidingModal = (configuration, saveMethodConfigurationUrl, languages) => {
        const {
            elementGenerator: generator,
            translationService,
            utilities,
            validationService: validator
        } = OnlinePaymentsFE;
        const api = OnlinePaymentsFE.ajaxService;
        /** @type {SlidingModalModel|null} */
        let activeMethod = null;
        /** @type {SlidingModalModel|null} */
        let changedMethod = null;
        /** @type {number} */
        let numberOfChanges = 0;
        let name = null;
        let vaultTitle = null;

        /**
         * Handles form input field change.
         *
         * @param {string} prop
         * @param {any} value
         * @param {boolean?} additional
         */
        const handleConfigMethodChange = (prop, value, additional) => {
            if (prop !== 'logo') {
                value = OnlinePaymentsFE.sanitize(value);
            }

            const areDifferent = (source, target) => {
                if (Array.isArray(source) && Array.isArray(target)) {
                    return !OnlinePaymentsFE.utilities.compareArrays(source, target);
                }

                return source !== target;
            };

            const areArraysEqual = (prop, additional) => {
                let changed = additional ? changedMethod.additionalData[prop] : changedMethod[prop];
                let active = additional ? activeMethod.additionalData[prop] : activeMethod[prop];

                if (changed.length !== active.length) {
                    numberOfChanges++;
                    return;
                }

                const sorted1 = [...changed].sort((a, b) => a.locale.localeCompare(b.locale));
                const sorted2 = [...active].sort((a, b) => a.locale.localeCompare(b.locale));

                let result = sorted1.every((item, index) => {
                    return item.locale === sorted2[index].locale && item.value === sorted2[index].value;
                });

                if (!result) {
                    numberOfChanges++;
                }
            }

            numberOfChanges = 0;
            if (additional) {
                if (!changedMethod.additionalData) {
                    changedMethod.additionalData = {};
                }

                changedMethod.additionalData[prop] = value;
            } else if (prop === 'logo') {
                changedMethod.logoFile = value;
                numberOfChanges = 1;
            } else {
                changedMethod[prop] = value;
            }

            Object.entries(changedMethod).forEach(([prop, value]) => {
                if (prop === 'additionalData') {
                    Object.entries(changedMethod.additionalData).forEach(([prop, value]) => {
                        if (prop === 'vaultTitleCollection') {
                            areArraysEqual(prop, true);
                        } else if (prop !== 'logo'){
                            areDifferent(activeMethod.additionalData[prop], value) && numberOfChanges++;
                        }
                    });
                } else {
                    if (prop === 'name') {
                        areArraysEqual(prop, false);
                    } else {
                        areDifferent(activeMethod[prop], value) && numberOfChanges++;
                    }
                }
            });

            if (numberOfChanges > 0) {
                const btn = document.querySelector('.op-save-btn');
                btn.classList.remove('ops--disabled');
            }

            if (numberOfChanges === 0) {
                const btn = document.querySelector('.op-save-btn');
                btn.classList.add('ops--disabled');
            }
        };

        const isValid = () => {
            let result = [validateRequiredField(['enabled', 'name'])];

            return !result.includes(false);
        };

        /**
         * Validates the additional form fields.
         *
         * @param {(keyof AdditionalDataConfig | 'name' | 'enabled' | 'paymentAction')[]} fieldNames
         * @returns {boolean[]}
         */
        const validateRequiredField = (fieldNames) => {
            return fieldNames.map((fieldName) =>
                validator.validateRequiredField(form.querySelector(`[name=${fieldName}]`))
            );
        };

        const createHeader = () => {
            const modalHeader = generator.createElement('div', 'op-header-name');
            const logo = generator.createElement('img', 'op-payment-logo');
            logo.src = configuration.logo;
            const nameWrapper = generator.createElement('div', 'op-name-wrapper');
            const name = generator.createElement('p', 'op-payment-method-name', configuration.name[0].value);
            nameWrapper.appendChild(name);
            configuration.integrationTypes.forEach((type) => {
                const typeElement = generator.createElement(
                    'span',
                    'op-integration-type ' + 'op-' + type,
                    'payments.integrationType.' + type
                );
                nameWrapper.appendChild(typeElement);
            });
            const closeButton = generator.createElement('div', 'op-close-button');
            closeButton.addEventListener('click', () => {
                let modal = document.querySelector('.op-sliding-modal');
                let mask = document.querySelector('.op-dark-mask');

                mask.remove();
                modal.remove();
            });

            modalHeader.appendChild(logo);
            modalHeader.appendChild(nameWrapper);
            modalHeader.appendChild(closeButton);

            return modalHeader;
        };

        function getInstantPaymentField() {
            return generator.createFormFields([
                {
                    name: 'instantPayment',
                    value: configuration.additionalData.instantPayment,
                    type: 'checkbox',
                    className: '',
                    label: `payments.configure.fields.instantPayment.label`,
                    description: `payments.configure.fields.instantPayment.description`,
                    onChange: (value) => handleConfigMethodChange('instantPayment', value, true)
                }
            ]);
        }

        function getSepaFields() {
            return generator.createFormFields(
                [
                    {
                        name: 'recurrenceType',
                        value: configuration.additionalData.recurrenceType,
                        type: 'dropdown',
                        label: 'payments.configure.fields.directDebitRecurrence.label',
                        options: [
                            { label: 'payments.configure.fields.directDebitRecurrence.unique', value: 'unique' },
                            {
                                label: 'payments.configure.fields.directDebitRecurrence.recurring',
                                value: 'recurring'
                            }
                        ],
                        onChange: (value) => handleConfigMethodChange('recurrenceType', value, true)
                    },
                    {
                        name: 'signatureType',
                        value: configuration.additionalData.signatureType,
                        type: 'dropdown',
                        label: 'payments.configure.fields.signatureType.label',
                        options: [
                            { label: 'payments.configure.fields.signatureType.SMS', value: 'SMS' },
                            { label: 'payments.configure.fields.signatureType.UNSIGNED', value: 'UNSIGNED' }
                        ],
                        onChange: (value) => handleConfigMethodChange('signatureType', value, true)
                    }
                ]
            );
        }

        function getIntersolveFields() {
            return generator.createFormFields(
                [
                    {
                        name: 'sessionTimeout',
                        value: configuration.additionalData.sessionTimeout,
                        type: 'number',
                        label: 'payments.configure.fields.sessionTimeout.label',
                        description: 'payments.configure.fields.sessionTimeout.description',
                        onChange: (value) => handleConfigMethodChange('sessionTimeout', value, true)
                    },
                    {
                        name: 'paymentProductId',
                        value: configuration.additionalData.paymentProductId,
                        type: 'number',
                        label: 'payments.configure.fields.productId.label',
                        description: 'payments.configure.fields.productId.description',
                        onChange: (value) => handleConfigMethodChange('paymentProductId', value, true)
                    }
                ]
            );
        }

        function getOneyFields() {
            return generator.createFormFields(
                [
                    {
                        name: 'paymentOption',
                        value: configuration.additionalData.paymentOption,
                        type: 'text',
                        label: 'payments.configure.fields.paymentOption.label',
                        onChange: (value) => handleConfigMethodChange('paymentOption', value, true)
                    }
                ]
            );
        }

        function getHostedCheckoutFields() {
            return generator.createFormFields(
                [
                    {
                        name: 'logo',
                        value: configuration.additionalData.logo,
                        type: 'file',
                        supportedMimeTypes: ['image/jpeg', 'image/jpg', 'image/png'],
                        label: 'payments.configure.fields.logo.label',
                        description: OnlinePaymentsFE.brand.code + '.payments.hostedCheckout.logo.description',
                        onChange: (value) => handleConfigMethodChange('logo', value, false)
                    },
                    {
                        name: 'enableGroupCards',
                        value: configuration.additionalData.enableGroupCards,
                        type: 'checkbox',
                        className: '',
                        label: `payments.configure.fields.enableGroupCards.label`,
                        description: `payments.configure.fields.enableGroupCards.description`,
                        onChange: (value) => handleConfigMethodChange('enableGroupCards', value === true, true)
                    }
                ]
            );
        }

        function handleSave(event) {
            preventDefaults(event);
            utilities.showLoader();

            const data = {
                ...changedMethod,
                additionalData: utilities.cloneObject(changedMethod.additionalData)
            };

            if (!isValid()) {
                utilities.hideLoader();
                return;
            }

            const postData = new FormData();
            Object.entries(data).forEach(([key, value]) => {
                if (key !== 'logoFile' && key !== 'additionalData' && key !== 'name') {
                    postData.append(key, value);
                }
            });

            postData.append('name', JSON.stringify(data.name));
            postData.append('additionalData', JSON.stringify(data.additionalData || null));

            if (data.logoFile) {
                postData.set('logo', data.logoFile, data.logoFile.name);
            }

            const url = saveMethodConfigurationUrl.replace('{methodId}', activeMethod.paymentProductId);
            api.post(url, postData, {
                'Content-Type': 'multipart/form-data'
            }).then(() => {
                utilities.createToasterMessage('payments.configure.methodSaved');
                utilities.hideLoader();
                OnlinePaymentsFE.state.display();
            }).catch((error) => {
                utilities.createToasterMessage(error, 'error');
                utilities.hideLoader();
            });
        }

        const createDefaultForm = () => {
            const form = generator.createElement('form', 'op-form');

            const enabled = generator.createFormFields([
                {
                    name: 'enabled',
                    value: configuration.enabled,
                    type: 'checkbox',
                    className: '',
                    label: `payments.configure.fields.enableOnCheckout.label`,
                    description: `payments.configure.fields.enableOnCheckout.description`,
                    onChange: (value) => handleConfigMethodChange('enabled', value, false)
                }
            ]);
            form.appendChild(enabled[0]);

            name = OnlinePaymentsFE.components.TranslatableLabel.create({
                onChange: () => {
                    handleConfigMethodChange('name', name.getValues(), false);
                },
                name: 'name',
                languages: languages,
                translations: activeMethod.name
            });
            let nameWrapper = generator.createFieldWrapper(
                name.element,
                translationService.translate('payments.configure.fields.name.label'),
                translationService.translate('payments.configure.fields.name.description')
            );
            form.appendChild(nameWrapper);

            const template = generator.createFormFields(
                [
                    {
                        name: 'template',
                        value: configuration.templateName,
                        type: 'text',
                        label: 'payments.configure.fields.templateName.label',
                        description: 'payments.configure.fields.templateName.description',
                        onChange: (value) => handleConfigMethodChange('template', value, false)
                    }
                ]
            );

            form.appendChild(template[0]);

            if (configuration.paymentProductId === 'cards') {
                vaultTitle = OnlinePaymentsFE.components.TranslatableLabel.create({
                    onChange: () => {
                        handleConfigMethodChange('vaultTitleCollection', vaultTitle.getValues(), true);
                    },
                    name: 'name',
                    languages: languages,
                    translations: activeMethod.additionalData.vaultTitleCollection
                });

                let vaultTitleWrapper = generator.createFieldWrapper(
                    vaultTitle.element,
                    translationService.translate('payments.configure.fields.vaultTitle.label')
                );

                form.appendChild(vaultTitleWrapper);
            }

            if (configuration.paymentProductId === '5408') {
                let instantPayment = getInstantPaymentField();
                form.appendChild(instantPayment[0]);
            }

            if (configuration.paymentProductId === '771') {
                let sepaFields = getSepaFields();
                form.append(...sepaFields);
            }

            if (configuration.paymentProductId === '5700') {
                let intersolveFields = getIntersolveFields();
                form.append(...intersolveFields);
            }

            if (['5111', '5112', '5127', '5125'].includes(configuration.paymentProductId)) {
                let oneyFields = getOneyFields();
                form.append(...oneyFields);
            }

            if (configuration.paymentProductId === 'hosted_checkout') {
                let hostedCheckoutFields = getHostedCheckoutFields();
                form.append(...hostedCheckoutFields);
            }

            const buttons = generator.createElement('div', 'op-button-bar');
            const saveButton = generator.createElement(
                'button',
                'op-save-btn op-button opt--primary ops--disabled',
                translationService.translate('general.saveChanges')
            );
            const cancelButton = generator.createElement(
                'button',
                'op-button opt--secondary',
                translationService.translate('general.cancel')
            );
            cancelButton.addEventListener('click', function(event) {
                preventDefaults(event);
                let mask = document.querySelector('.op-dark-mask');
                mask.remove();
                document.querySelector('.op-sliding-modal').classList.remove('op-open');
            });

            saveButton.addEventListener('click', function(event) {
                handleSave(event);
            });

            buttons.appendChild(saveButton);
            buttons.appendChild(cancelButton);
            form.appendChild(buttons);

            return form;
        };

        activeMethod = utilities.cloneObject(configuration);
        changedMethod = utilities.cloneObject(configuration);
        const modal = generator.createElement('div', 'op-sliding-modal');

        const paymentGroup = generator.createElement('div', 'op-payment-group',
            'payments.paymentGroups.' + configuration.paymentGroup);
        const form = createDefaultForm();

        modal.appendChild(createHeader());
        modal.appendChild(paymentGroup);
        modal.appendChild(form);

        return modal;
    };

    OnlinePaymentsFE.components.SlidingModal = {
        /**
         * @param {SlidingModalModel} config
         * @param {string} saveMethodConfigurationUrl
         * @param {Language[]} languages
         * @returns {HTMLElement}
         */
        create: (config, saveMethodConfigurationUrl, languages) => SlidingModal(config, saveMethodConfigurationUrl, languages)
    };
})();