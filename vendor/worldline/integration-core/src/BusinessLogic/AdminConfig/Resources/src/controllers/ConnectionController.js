if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

(function () {
    /**
     * @typedef ConnectionInfo
     * @property {string} pspid
     * @property {string} apiKey
     * @property {string} apiSecret
     * @property {string} webhooksKey
     * @property {string} webhooksSecret
     */

    /**
     * @typedef Connection
     * @property {'test' | 'live'} mode
     * @property {ConnectionInfo?} sandboxData
     * @property {ConnectionInfo?} liveData
     */

    /**
     * @typedef ConnectionSettings
     * @property {'test' | 'live'} mode
     * @property {string} pspid
     * @property {string} apiKey
     * @property {string} apiSecret
     * @property {string} webhooksKey
     * @property {string} webhooksSecret
     */
    /**
     * Handles connection page logic.
     *
     * @param {{getSettingsUrl: string, submitUrl: string, webhooksUrl: string}} configuration
     * @constructor
     */
    function ConnectionController(configuration) {
        /** @type AjaxServiceType */
        const api = OnlinePaymentsFE.ajaxService;

        const {
            templateService,
            elementGenerator: generator,
            validationService: validator,
            translationService: translationService,
            components,
            state,
            utilities
        } = OnlinePaymentsFE;
        /** @type {HTMLElement} */
        let form;
        let currentStoreId;
        /** @type {ConnectionSettings} */
        let activeSettings;
        /** @type {ConnectionSettings} */
        let changedSettings;

        /**
         * Displays page content.
         *
         * @param {{ state?: string, storeId: string }} config
         */
        this.display = ({ storeId }) => {
            utilities.showLoader();
            currentStoreId = storeId;
            templateService.clearMainPage();

            configuration.getSettingsUrl = configuration.getSettingsUrl.replace('{storeId}', storeId);
            configuration.submitUrl = configuration.submitUrl.replace('{storeId}', storeId);

            state
                .getCurrentMerchantState()
                .then((state) => {
                    return api.get(configuration.getSettingsUrl, () => null).then(createForm);
                })
                .finally(() => {
                    utilities.hideLoader();
                });
        };

        /**
         * Sets the unsaved changes.
         *
         * @return {boolean}
         */
        this.hasUnsavedChanges = () => false;

        /**
         * Renders the form.
         *
         * @param {ConnectionSettings} data
         */
        const renderForm = (data) => {
            const content = generator.createElement('div', 'op-connection-page');
            form = generator.createElement('form');
            const title = generator.createElement('p', 'op-connection-title', 'connection.title');
            const webhookUrlDiv = generator.createElement(
                'div',
                'op-webhooks-url-wrapper'
            );
            const webhooksUrl = generator.createElement(
                'span',
                'op-webhooks-url',
                state.formatUrl(configuration.webhooksUrl)
            );
            const webhookCopy = generator.createElement(
                'span',
                'op-webhooks-url-copy'
            );
            webhookCopy.addEventListener('click', function () {
                navigator.clipboard.writeText(state.formatUrl(configuration.webhooksUrl));
            });
            webhookUrlDiv.appendChild(webhooksUrl);
            webhookUrlDiv.appendChild(webhookCopy);
            const webhooksUrlWrapper = generator.createFieldWrapper(
                webhookUrlDiv,
                'connection.webhooksUrl.title',
                'connection.webhooksUrl.description'
            );
            const headerSection = generator.createElement('div', 'op-header-section', '', { id: 'op-header-section' });

            const components = [
                title,
                generator.createDropdownField({
                    name: 'mode',
                    value: data.mode || 'test',
                    label: 'connection.mode.title',
                    description: 'connection.mode.description',
                    options: [
                        { label: 'connection.mode.options.sandbox', value: 'test' },
                        { label: 'connection.mode.options.live', value: 'live' }
                    ],
                    onChange: (value) => handleChange('mode', value)
                }),
                generator.createTextField({
                    name: 'pspid',
                    value: data.pspid,
                    label: 'connection.pspid.title',
                    description: 'connection.pspid.description',
                    error: 'connection.pspid.error',
                    onChange: (value) => handleChange('pspid', value)
                }),
                generator.createTextField({
                    name: 'apiKey',
                    value: data.apiKey,
                    label: 'connection.apiKey.title',
                    description: 'connection.apiKey.description',
                    error: 'connection.apiKey.error',
                    onChange: (value) => handleChange('apiKey', value)
                }),
                generator.createPasswordField({
                    name: 'apiSecret',
                    value: data.apiSecret,
                    label: 'connection.apiSecret.title',
                    placeholder: translationService.translate(
                        'connection.apiSecret.placeholder',
                        [data.mode || 'sandbox']
                    ),
                    description: 'connection.apiSecret.description',
                    error: 'connection.apiSecret.error',
                    onChange: (value) => handleChange('apiSecret', value)
                }),
                generator.createTextField({
                    name: 'webhooksKey',
                    value: data.webhooksKey,
                    label: 'connection.webhooksKey.title',
                    placeholder: translationService.translate('connection.webhooksKey.placeholder'),
                    description: 'connection.webhooksKey.description',
                    error: 'connection.webhooksKey.error',
                    onChange: (value) => handleChange('webhooksKey', value)
                }),
                generator.createPasswordField({
                    name: 'webhooksSecret',
                    value: data.webhooksSecret,
                    label: 'connection.webhooksSecret.title',
                    placeholder: translationService.translate('connection.webhooksSecret.placeholder'),
                    description: 'connection.webhooksSecret.description',
                    error: 'connection.webhooksSecret.error',
                    onChange: (value) => handleChange('webhooksSecret', value)
                }),
                webhooksUrlWrapper
            ];

            form.append(...components);

            const connectButton = generator.createButton({
                type: 'primary',
                name: 'saveButton',
                disabled: !data.apiKey,
                label: 'connection.connect',
                onClick: handleFormSubmit
            })
            const buttonWrapper = generator.createElement('div', 'op-button-wrapper');

            buttonWrapper.append(connectButton);
            form.append(buttonWrapper);

            content.append(headerSection);
            content.append(form);
            templateService.clearMainPage();
            templateService.getMainPage().append(content);
        };

        /**
         * Creates the form.
         *
         * @param {Connection?} settings
         */
        const createForm = (settings) => {
            const mode = settings?.mode || 'test';
            /** @type ConnectionSettings */
            const data = { mode: mode, pspid: '', apiKey: '', apiSecret: '', webhooksKey: '', webhooksSecret: '' };
            if (settings?.[`${mode}Data`]) {
                data.pspid = settings[`${mode}Data`].pspid;
                data.apiKey = settings[`${mode}Data`].apiKey;
                data.apiSecret = settings[`${mode}Data`].apiSecret;
                data.webhooksKey = settings[`${mode}Data`].webhooksKey;
                data.webhooksSecret = settings[`${mode}Data`].webhooksSecret;
            }

            changedSettings = utilities.cloneObject(data);
            activeSettings = utilities.cloneObject(data);

            renderForm(data);

            return Promise.resolve();
        };

        /**
         *
         * @param {keyof ConnectionSettings} prop
         * @param {any} value
         */
        const handleChange = (prop, value) => {
            changedSettings[prop] = value;
            if (prop === 'mode') {
                changedSettings.apiKey = '';
                changedSettings.apiSecret = '';
                changedSettings.pspid = '';
                changedSettings.webhooksKey = '';
                changedSettings.webhooksSecret = '';
                form['apiKey'].value = '';
                form['pspid'].value = '';
                form['apiSecret'].value = '';
                form['webhooksKey'].value = '';
                form['webhooksSecret'].value = '';
            } else {
                validator.validateRequiredField(form[prop], 'connection.' + prop + '.error');
            }

            form['saveButton'].disabled = !form['pspid'].value || !form['apiKey'].value
                || !form['apiSecret'].value || !form['webhooksKey'].value || !form['webhooksSecret'].value;
        };

        /**
         * Converts form data to the settings object.
         *
         * @return {Connection}
         */
        const getFormData = () => ({
            mode: changedSettings.mode,
            [changedSettings.mode + 'Data']: {
                pspid: changedSettings.pspid,
                apiKey: changedSettings.apiKey,
                apiSecret: changedSettings.apiSecret,
                webhooksKey: changedSettings.webhooksKey,
                webhooksSecret: changedSettings.webhooksSecret
            }
        });

        /**
         * Saves the connection configuration.
         *
         * @returns {boolean}
         */
        const handleFormSubmit = () => {
            const isValid =
                validator.validateRequiredField(form['mode']) &&
                validator.validateRequiredField(form['pspid'], 'connection.pspid.error') &&
                validator.validateRequiredField(form['apiKey'], 'connection.apiKey.error') &&
                validator.validateRequiredField(form['apiSecret'], 'connection.apiSecret.error') &&
                validator.validateRequiredField(form['webhooksKey'], 'connection.webhooksKey.error') &&
                validator.validateRequiredField(form['webhooksSecret'], 'connection.webhooksSecret.error');

            if (isValid) {
                utilities.showLoader();
                api.post(configuration.submitUrl, getFormData())
                    .then(handleSaveSuccess)
                    .finally(() => {
                        utilities.hideLoader();
                    });
            }

            return false;
        };

        const handleSaveSuccess = () => {
            const finishSave = () => {
                activeSettings = { ...changedSettings };
                showFlashMessage('connection.messages.connectionUpdated', 'success');
            };

            utilities.remove401Message();
            state.enableHeaderTabs();
            state.display();
            state.setHeader();
            finishSave();
        };

        /**
         * Shows the disconnect confirmation modal.
         */
        const showDisconnectModal = () => {
            showConfirmModal('disconnect').then((confirmed) => confirmed && handleDisconnect());
        };

        /**
         * Shows the confirmation modal dialog.
         *
         * @param {string} type
         * @returns {Promise}
         */
        const showConfirmModal = (type) => {
            return new Promise((resolve) => {
                const modal = components.Modal.create({
                    title: `connection.${type}Modal.title`,
                    className: `op-confirm-modal`,
                    content: [generator.createElement('p', '', `connection.${type}Modal.message`)],
                    footer: true,
                    buttons: [
                        {
                            type: 'secondary',
                            label: 'general.cancel',
                            onClick: () => {
                                modal.close();
                                resolve(false);
                            }
                        },
                        {
                            type: 'primary',
                            className: 'opm--destructive',
                            label: 'general.confirm',
                            onClick: () => {
                                modal.close();
                                resolve(true);
                            }
                        }
                    ]
                });

                modal.open();
            });
        };

        const handleDisconnect = () => {
            utilities.showLoader();
            api.delete(configuration.disconnectUrl)
                .then(() => {
                    window.location.reload();
                })
                .finally(() => {
                    utilities.hideLoader();
                });
        };

        /**
         * Displays the flash message.
         *
         * @param {string} message Translation key or message
         * @param {'success' | 'error'} status
         */
        const showFlashMessage = (message, status = 'success') => {
            const container = form?.querySelector('.opp-flash-message-wrapper');
            if (!container) {
                return;
            }

            templateService.clearComponent(container);
            container.append(utilities.createToasterMessage(message, status));
            container.scrollIntoView({ behavior: 'smooth' });
        };
    }

    OnlinePaymentsFE.ConnectionController = ConnectionController;
})();
