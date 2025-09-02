if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

(function () {
    /**
     * @typedef PaymentStatus
     *
     * @property {string} label
     * @property {string} value
     */

    /**
     * @typedef ConnectionInfo
     * @property {string} pspid
     * @property {string} apiKey
     * @property {string} apiSecret
     * @property {string} webhooksKey
     * @property {string} webhooksSecret
     */

    /**
     * @typedef AccountSettings
     *
     * @property {'test' | 'live'} mode
     * @property {ConnectionInfo?} sandboxData
     * @property {ConnectionInfo?} liveData
     */

    /**
     * @typedef PaymentSettings
     *
     * @property {'authorize' | 'authorize-capture'} paymentAction
     * @property {-1 | 60 | 120 | 240 | 480 | 1440 | 2880 | 7200} automaticCapture
     * @property {int} numberOfPaymentAttempts
     * @property {boolean} applySurcharge
     * @property {string} paymentCapturedStatus
     * @property {string} paymentErrorStatus
     * @property {string} paymentPendingStatus
     * @property {string} paymentAuthorizedStatus
     * @property {string} paymentCancelledStatus
     * @property {string} paymentRefundedStatus
     */

    /**
     * @typedef CardsSettings
     *
     * @property {boolean} enable3ds
     * @property {boolean} enforceStrongAuthentication
     * @property {boolean} enable3dsExemption
     * @property {'low-value' | 'transaction-risk-analysis'} exemptionType
     * @property {float} exemptionLimit
     */

    /**
     * @typedef LogSettings
     *
     * @property {boolean} debugMode
     * @property {int} logDays
     */


    /**
     * @typedef PayByLinkSettings
     *
     * @property {boolean} enabled
     * @property {string} title
     * @property {int} expirationTime
     */

    /**
     * @typedef GeneralSettings
     *
     * @property {AccountSettings} accountSettings
     * @property {PaymentSettings} paymentSettings
     * @property {CardsSettings} cardsSettings
     * @property {LogSettings} logSettings
     * @property {PayByLinkSettings} payByLinkSettings
     */

    /**
     * Handles settings page logic.
     *
     * @param {{
     *  getGeneralSettingsUrl: string,
     *  getPaymentStatusesUrl: string,
     *  saveConnectionUrl: string,
     *  saveCardsSettingsUrl: string,
     *  savePaymentSettingsUrl: string,
     *  saveLogSettingsUrl: string,
     *  savePayByLinkSettingsUrl: string,
     *  webhooksUrl: string
     *  disconnectUrl: string}}  configuration
     * @constructor
     */
    function SettingsController(configuration) {
        /** @type AjaxServiceType */
        const api = OnlinePaymentsFE.ajaxService;

        const translationService = OnlinePaymentsFE.translationService;

        const {
            templateService,
            elementGenerator: generator,
            validationService: validator,
            utilities
        } = OnlinePaymentsFE;
        /** @type string */
        let currentStoreId = '';
        /** @type HTMLElement | null */
        let accountForm = null;
        /** @type HTMLElement | null */
        let paymentForm = null;
        /** @type HTMLElement | null */
        let cardsForm = null;
        /** @type HTMLElement | null */
        let logForm = null;
        /** @type HTMLElement | null */
        let payByLinkForm = null;
        /** @type HTMLElement | null */
        let disconnectForm = null;

        /** @type AccountSettings */
        let activeAccountSettings;
        /** @type AccountSettings */
        let changedAccountSettings;
        /** @type CardsSettings */
        let activeCardsSettings;
        /** @type CardsSettings */
        let changedCardsSettings;
        /** @type PaymentSettings */
        let activePaymentSettings;
        /** @type PaymentSettings */
        let changedPaymentSettings;
        /** @type LogSettings */
        let activeLogSettings;
        /** @type LogSettings */
        let changedLogSettings;
        /** @type PayByLinkSettings */
        let activePayByLinkSettings;
        /** @type PayByLinkSettings */
        let changedPayByLinkSettings;

        /** @type PaymentStatus[] */
        let paymentStatuses;

        /**
         * Displays page content.
         *
         * @param {{ state?: string, storeId: string }} config
         */
        this.display = ({storeId}) => {
            currentStoreId = storeId;
            templateService.clearMainPage();
            [
                'getGeneralSettingsUrl',
                'getPaymentStatusesUrl',
                'saveConnectionUrl',
                'saveCardsSettingsUrl',
                'savePaymentSettingsUrl',
                'saveLogSettingsUrl',
                'savePayByLinkSettingsUrl',
                'webhooksUrl',
                'disconnectUrl'
            ].forEach((prop) => {
                configuration[prop] = configuration[prop].replace('{storeId}', storeId);
            });

            return renderPage();
        };

        /**
         * Sets the unsaved changes.
         *
         * @return {boolean}
         */
        this.hasUnsavedChanges = () => false;

        const scrollToTop = () => {
            document.querySelector('#op-page > main')?.scrollTo({top: 0, left: 0, behavior: 'smooth'});
        };

        const renderPage = () => {
            utilities.showLoader();

            scrollToTop();
            let url = configuration.getGeneralSettingsUrl;
            let renderer = renderGeneralSettingsForm;

            return api
                .get(url, () => null)
                .then(renderer)
                .catch(renderer);
        };

        /**
         * Renders the general settings form.
         *
         * @param {GeneralSettings} settings
         */
        const renderGeneralSettingsForm = (settings) => {
            let url = configuration.getPaymentStatusesUrl;

            api.get(url, () => null)
                .then((response) => {
                    paymentStatuses = response;

                    activeAccountSettings = utilities.cloneObject(settings.accountSettings);
                    activePaymentSettings = utilities.cloneObject(settings.paymentSettings);
                    activeCardsSettings = utilities.cloneObject(settings.cardsSettings);
                    activeLogSettings = utilities.cloneObject(settings.logSettings);
                    activePayByLinkSettings = utilities.cloneObject(settings.payByLinkSettings);

                    changedAccountSettings = utilities.cloneObject(settings.accountSettings);
                    changedPaymentSettings = utilities.cloneObject(settings.paymentSettings);
                    changedCardsSettings = utilities.cloneObject(settings.cardsSettings);
                    changedLogSettings = utilities.cloneObject(settings.logSettings);
                    changedPayByLinkSettings = utilities.cloneObject(settings.payByLinkSettings);

                    let content = generator.createElement('div', 'op-settings-page');

                    content.appendChild(renderAccountForm());
                    content.appendChild(generator.createElement('div', 'op-divider'));
                    content.appendChild(renderPaymentSettingsForm());
                    content.appendChild(generator.createElement('div', 'op-divider'));
                    content.appendChild(renderCardsForm());
                    content.appendChild(generator.createElement('div', 'op-divider'));
                    content.appendChild(renderLogForm());
                    content.appendChild(generator.createElement('div', 'op-divider'));
                    content.appendChild(renderPayByLinkForm());
                    content.appendChild(generator.createElement('div', 'op-divider'));
                    content.appendChild(renderDisconnectForm());

                    templateService.getMainPage().appendChild(content);

                    handleDependencies('enable3ds', activeCardsSettings.enable3ds);
                    handleDependencies('enforceStrongAuthentication', activeCardsSettings.enforceStrongAuthentication);
                    handleDependencies('enable3dsExemption', activeCardsSettings.enable3dsExemption);
                    handlePayByLinkDependencies('enabled', activePayByLinkSettings.enabled);
                })
                .finally(() => {
                        let header = templateService.getHeaderSection();
                        let title = header.querySelector('.op-main-title');
                        title.innerText = translationService.translate('generalSettings.title');
                        utilities.hideLoader();
                        OnlinePaymentsFE.state.setHeader();
                    }
                );
        };

        const renderAccountForm = () => {
            if (accountForm) {
                templateService.clearComponent(accountForm);
            }

            let activeConnection = activeAccountSettings.liveData;
            if (activeAccountSettings.mode === 'test') {
                activeConnection = activeAccountSettings.sandboxData;
            }

            const webhookUrlDiv = generator.createElement(
                'div',
                'op-webhooks-url-wrapper'
            );
            const webhooksUrl = generator.createElement(
                'span',
                'op-webhooks-url',
                OnlinePaymentsFE.state.formatUrl(configuration.webhooksUrl)
            );
            const webhookCopy = generator.createElement(
                'span',
                'op-webhooks-url-copy'
            );
            webhookCopy.addEventListener('click', function () {
                navigator.clipboard.writeText(OnlinePaymentsFE.state.formatUrl(configuration.webhooksUrl));
            });
            webhookUrlDiv.appendChild(webhooksUrl);
            webhookUrlDiv.appendChild(webhookCopy);
            const webhooksUrlWrapper = generator.createFieldWrapper(
                webhookUrlDiv,
                'connection.webhooksUrl.title',
                'connection.webhooksUrl.description'
            );

            const saveButton = generator.createButton({
                type: 'primary',
                name: 'saveAccountBtn',
                disabled: true,
                label: 'general.saveChanges',
                onClick: () => {
                    let mode = document.querySelector('[name="mode"]'),
                        apiKey = document.querySelector('[name="apiKey"]'),
                        pspid = document.querySelector('[name="pspid"]'),
                        apiSecret = document.querySelector('[name="apiSecret"]'),
                        webhooksKey = document.querySelector('[name="webhooksKey"]'),
                        webhooksSecret = document.querySelector('[name="webhooksSecret"]');
                    const isValid =
                        validator.validateRequiredField(mode) &&
                        validator.validateRequiredField(pspid, 'connection.pspid.error') &&
                        validator.validateRequiredField(apiKey, 'connection.apiKey.error') &&
                        validator.validateRequiredField(apiSecret, 'connection.apiSecret.error') &&
                        validator.validateRequiredField(webhooksKey, 'connection.webhooksKey.error') &&
                        validator.validateRequiredField(webhooksSecret, 'connection.webhooksSecret.error');

                    if (isValid) {
                        utilities.showLoader();
                        api.post(
                            configuration.saveConnectionUrl,
                            {
                                mode: changedAccountSettings.mode,
                                testData: {
                                    pspid: changedAccountSettings.sandboxData.pspid,
                                    apiKey: changedAccountSettings.sandboxData.apiKey,
                                    apiSecret: changedAccountSettings.sandboxData.apiSecret,
                                    webhooksKey: changedAccountSettings.sandboxData.webhooksKey,
                                    webhooksSecret: changedAccountSettings.sandboxData.webhooksSecret
                                },
                                liveData: {
                                    pspid: changedAccountSettings.liveData.pspid,
                                    apiKey: changedAccountSettings.liveData.apiKey,
                                    apiSecret: changedAccountSettings.liveData.apiSecret,
                                    webhooksKey: changedAccountSettings.liveData.webhooksKey,
                                    webhooksSecret: changedAccountSettings.liveData.webhooksSecret
                                }
                            }
                        )
                            .then(() => {
                                handleSaveSuccess('accountSettings');
                            })
                            .catch(() => {
                                handleSaveFailure('accountSettings');
                            })
                            .finally(() => {
                                utilities.hideLoader();
                            });
                    }
                }
            });
            const buttonWrapper = generator.createElement('div', 'op-button-wrapper');

            buttonWrapper.append(saveButton);

            accountForm = generator.createElement('div', 'op-card', '', null, [
                generator.createElement('div', 'op-card-title', '', null, [
                    generator.createElement(
                        'h1',
                        '',
                        translationService.translate('generalSettings.accountSettings.title')
                    ),
                    generator.createElement(
                        'p',
                        '',
                        translationService.translate(OnlinePaymentsFE.brand.code + '.generalSettings.accountSettings.description')
                    )
                ]),
                generator.createElement('div', 'op-card-content', '', null, [
                    generator.createDropdownField({
                        name: 'mode',
                        value: activeAccountSettings.mode || 'test',
                        label: 'connection.mode.title',
                        description: 'connection.mode.description',
                        options: [
                            {label: 'connection.mode.options.sandbox', value: 'test'},
                            {label: 'connection.mode.options.live', value: 'live'}
                        ],
                        onChange: (value) => handleAccountSettingsChange('mode', value)
                    }),
                    generator.createTextField({
                        name: 'pspid',
                        value: activeConnection.pspid,
                        label: 'connection.pspid.title',
                        description: 'connection.pspid.description',
                        error: 'connection.pspid.error',
                        onChange: (value) => handleAccountSettingsChange('pspid', value)
                    }),
                    generator.createTextField({
                        name: 'apiKey',
                        value: activeConnection.apiKey,
                        label: 'connection.apiKey.title',
                        description: 'connection.apiKey.description',
                        error: 'connection.apiKey.error',
                        onChange: (value) => handleAccountSettingsChange('apiKey', value)
                    }),
                    generator.createPasswordField({
                        name: 'apiSecret',
                        value: activeConnection.apiSecret,
                        label: 'connection.apiSecret.title',
                        placeholder: translationService.translate(
                            'connection.apiSecret.placeholder',
                            [activeAccountSettings.mode || 'sandbox']
                        ),
                        description: 'connection.apiSecret.description',
                        error: 'connection.apiSecret.error',
                        onChange: (value) => handleAccountSettingsChange('apiSecret', value)
                    }),
                    generator.createTextField({
                        name: 'webhooksKey',
                        value: activeConnection.webhooksKey,
                        label: 'connection.webhooksKey.title',
                        placeholder: translationService.translate('connection.webhooksKey.placeholder'),
                        description: 'connection.webhooksKey.description',
                        error: 'connection.webhooksKey.error',
                        onChange: (value) => handleAccountSettingsChange('webhooksKey', value)
                    }),
                    generator.createPasswordField({
                        name: 'webhooksSecret',
                        value: activeConnection.webhooksSecret,
                        label: 'connection.webhooksSecret.title',
                        placeholder: translationService.translate('connection.webhooksSecret.placeholder'),
                        description: 'connection.webhooksSecret.description',
                        error: 'connection.webhooksSecret.error',
                        onChange: (value) => handleAccountSettingsChange('webhooksSecret', value)
                    }),
                    webhooksUrlWrapper,
                    buttonWrapper
                ])
            ]);

            return accountForm;
        };

        const handleAccountSettingsChange = (prop, value) => {
            if (changedAccountSettings.mode === 'test') {
                changedAccountSettings.sandboxData[prop] = value;
            } else {
                changedAccountSettings.liveData[prop] = value;
            }

            let apiKey = document.querySelector('[name="apiKey"]'),
                pspid = document.querySelector('[name="pspid"]'),
                apiSecret = document.querySelector('[name="apiSecret"]'),
                webhooksKey = document.querySelector('[name="webhooksKey"]'),
                webhooksSecret = document.querySelector('[name="webhooksSecret"]'),
                saveButton = document.querySelector('[name="saveAccountBtn"]');

            if (prop === 'mode') {
                let key = value === 'test' ? 'sandboxData' : 'liveData';

                apiKey.value = activeAccountSettings[key].apiKey;
                pspid.value = activeAccountSettings[key].pspid;
                apiSecret.value = activeAccountSettings[key].apiSecret;
                webhooksKey.value = activeAccountSettings[key].webhooksKey;
                webhooksSecret.value = activeAccountSettings[key].webhooksSecret;
                changedAccountSettings.mode = value;
            } else {
                let field = document.querySelector('[name="' + prop + '"]');

                validator.validateRequiredField(field, 'connection.' + prop + '.error');
            }

            saveButton.disabled = (!pspid.value || !apiKey.value || !apiSecret.value || !webhooksKey.value
                || !webhooksSecret.value) && changedAccountSettings !== activeAccountSettings;
        }

        const renderPaymentSettingsForm = () => {
            if (paymentForm) {
                templateService.clearComponent(paymentForm);
            }

            let statusOptions = [];

            paymentStatuses.forEach(status => {
                statusOptions.push({label: status.label, value: status.value});
            })

            let saveButton = generator.createButton({
                    type: 'primary',
                    name: 'paymentSettingsBtn',
                    disabled: true,
                    label: 'general.saveChanges',
                    onClick: () => {
                        let paymentAction = paymentForm.querySelector('[name="paymentAction"]'),
                            automaticCapture = paymentForm.querySelector('[name="automaticCapture"]'),
                            paymentCapturedStatus = paymentForm.querySelector('[name="paymentCapturedStatus"]'),
                            paymentErrorStatus = paymentForm.querySelector('[name="paymentErrorStatus"]'),
                            paymentPendingStatus = paymentForm.querySelector('[name="paymentPendingStatus"]');

                        let isValid =
                            validator.validateRequiredField(paymentAction) &&
                            validator.validateRequiredField(automaticCapture) &&
                            validator.validateRequiredField(paymentCapturedStatus) &&
                            validator.validateRequiredField(paymentErrorStatus) &&
                            validator.validateRequiredField(paymentPendingStatus);

                        if (isValid) {
                            utilities.showLoader();
                            api.post(configuration.savePaymentSettingsUrl, changedPaymentSettings)
                                .then(() => handleSaveSuccess('paymentSettings'))
                                .catch(() => handleSaveFailure('paymentSettings'))
                                .finally(() => {
                                    utilities.hideLoader();
                                });
                        }
                    }
                }
            );

            const buttonWrapper = generator.createElement('div', 'op-button-wrapper');
            buttonWrapper.append(saveButton);

            paymentForm = generator.createElement('div', 'op-card', '', null, [
                    generator.createElement('div', 'op-card-title', '', null, [
                        generator.createElement(
                            'h1',
                            '',
                            translationService.translate('generalSettings.paymentSettings.title')
                        ),
                        generator.createElement(
                            'p',
                            '',
                            translationService.translate('generalSettings.paymentSettings.description')
                        )
                    ]),
                    generator.createElement('div', 'op-card-content', '', null, [
                            generator.createDropdownField({
                                name: 'paymentAction',
                                value: activePaymentSettings.paymentAction || 'authorize-capture',
                                label: 'generalSettings.paymentSettings.paymentAction.title',
                                description: 'generalSettings.paymentSettings.paymentAction.description',
                                options: [
                                    {
                                        label: 'generalSettings.paymentSettings.paymentAction.values.authorize',
                                        value: 'FINAL_AUTHORIZATION'
                                    },
                                    {
                                        label: 'generalSettings.paymentSettings.paymentAction.values.authorizeCapture',
                                        value: 'SALE'
                                    }
                                ],
                                onChange: (value) => handlePaymentsSettingsChange('paymentAction', value)
                            }),
                            generator.createDropdownField({
                                name: 'automaticCapture',
                                value: activePaymentSettings.automaticCapture || -1,
                                label: 'generalSettings.paymentSettings.automaticCapture.title',
                                description: 'generalSettings.paymentSettings.automaticCapture.description',
                                options: [
                                    {label: 'generalSettings.paymentSettings.automaticCapture.values.never', value: -1},
                                    {label: 'generalSettings.paymentSettings.automaticCapture.values.oneHour', value: 60},
                                    {label: 'generalSettings.paymentSettings.automaticCapture.values.twoHours', value: 120},
                                    {
                                        label: 'generalSettings.paymentSettings.automaticCapture.values.fourHours',
                                        value: 240
                                    },
                                    {
                                        label: 'generalSettings.paymentSettings.automaticCapture.values.eightHours',
                                        value: 480
                                    },
                                    {label: 'generalSettings.paymentSettings.automaticCapture.values.oneDay', value: 1440},
                                    {label: 'generalSettings.paymentSettings.automaticCapture.values.twoDays', value: 2880},
                                    {
                                        label: 'generalSettings.paymentSettings.automaticCapture.values.fiveDays',
                                        value: 7200
                                    },
                                ],
                                onChange: (value) => handlePaymentsSettingsChange('automaticCapture', value)
                            }),
                            generator.createNumberField({
                                name: 'numberOfPaymentAttempts',
                                value: activePaymentSettings.numberOfPaymentAttempts || 10,
                                label: 'generalSettings.paymentSettings.attemptNumber.title',
                                description: 'generalSettings.paymentSettings.attemptNumber.description',
                                onChange: (value) => handlePaymentsSettingsChange('numberOfPaymentAttempts', value)
                            }),
                            generator.createFormFields([
                                {
                                    name: 'applySurcharge',
                                    value: activePaymentSettings.applySurcharge,
                                    type: 'checkbox',
                                    className: '',
                                    label: `generalSettings.paymentSettings.applySurcharge.title`,
                                    description: `generalSettings.paymentSettings.applySurcharge.description`,
                                    onChange: (value) => handlePaymentsSettingsChange('applySurcharge', value)
                                }
                            ])[0],
                            generator.createDropdownField({
                                name: 'paymentCapturedStatus',
                                value: activePaymentSettings.paymentCapturedStatus,
                                label: 'generalSettings.paymentSettings.paymentCapturedStatus.title',
                                description: 'generalSettings.paymentSettings.paymentCapturedStatus.description',
                                options: statusOptions,
                                onChange: (value) => handlePaymentsSettingsChange('paymentCapturedStatus', value)
                            }),
                            generator.createDropdownField({
                                name: 'paymentErrorStatus',
                                value: activePaymentSettings.paymentErrorStatus,
                                label: 'generalSettings.paymentSettings.paymentErrorStatus.title',
                                description: 'generalSettings.paymentSettings.paymentErrorStatus.description',
                                options: statusOptions,
                                onChange: (value) => handlePaymentsSettingsChange('paymentErrorStatus', value)
                            }),
                            generator.createDropdownField({
                                name: 'paymentPendingStatus',
                                value: activePaymentSettings.paymentPendingStatus,
                                label: 'generalSettings.paymentSettings.paymentPendingStatus.title',
                                description: 'generalSettings.paymentSettings.paymentPendingStatus.description',
                                options: statusOptions,
                                onChange: (value) => handlePaymentsSettingsChange('paymentPendingStatus', value)
                            }),
                            buttonWrapper
                        ]
                    )
                ]
            );

            return paymentForm;
        }

        const handlePaymentsSettingsChange = (prop, value) => {
            let paymentSettingsBtn = paymentForm.querySelector('[name="paymentSettingsBtn"]');

            if (prop === 'numberOfPaymentAttempts') {
                let numberOfAttempts = paymentForm.querySelector('[name="numberOfPaymentAttempts"]');
                if (value < 1 || value > 10) {
                    validator.setError(numberOfAttempts, 'generalSettings.paymentSettings.attemptNumber.error');
                    paymentSettingsBtn.disabled = true;

                    return;
                } else {
                    validator.removeError(numberOfAttempts);
                }
            }

            changedPaymentSettings[prop] = value;

            paymentSettingsBtn.disabled = changedPaymentSettings.paymentAction === activePaymentSettings.paymentAction &&
                changedPaymentSettings.paymentCapturedStatus === activePaymentSettings.paymentCapturedStatus &&
                changedPaymentSettings.paymentPendingStatus === activePaymentSettings.paymentPendingStatus &&
                changedPaymentSettings.paymentErrorStatus === activePaymentSettings.paymentErrorStatus &&
                changedPaymentSettings.numberOfPaymentAttempts === activePaymentSettings.numberOfPaymentAttempts &&
                changedPaymentSettings.applySurcharge === activePaymentSettings.applySurcharge &&
                changedPaymentSettings.automaticCapture === activePaymentSettings.automaticCapture;
        }

        const renderCardsForm = () => {
            if (cardsForm) {
                templateService.clearComponent(cardsForm);
            }

            let saveButton = generator.createButton({
                    type: 'primary',
                    name: 'cardSettingsBtn',
                    disabled: true,
                    label: 'general.saveChanges',
                    onClick: () => {
                        let exemptionType = cardsForm.querySelector('[name="exemptionType"]'),
                            exemptionLimit = cardsForm.querySelector('[name="exemptionLimit"]');
                        let isValid = true;

                        if (changedCardsSettings.enable3dsExemption) {
                            isValid = validator.validateRequiredField(exemptionType) &&
                                validator.validateRequiredField(exemptionLimit);
                        }

                        if (isValid) {
                            utilities.showLoader();
                            api.post(configuration.saveCardsSettingsUrl, changedCardsSettings)
                                .then(() => handleSaveSuccess('cardsSettings'))
                                .catch(() => handleSaveFailure('cardsSettings'))
                                .finally(() => {
                                    utilities.hideLoader();
                                });
                        }
                    }
                }
            );
            const buttonWrapper = generator.createElement('div', 'op-button-wrapper');
            buttonWrapper.append(saveButton);

            cardsForm = generator.createElement('div', 'op-card', '', null, [
                generator.createElement('div', 'op-card-title', '', null, [
                    generator.createElement(
                        'h1',
                        '',
                        translationService.translate('generalSettings.cardsSettings.title')
                    ),
                    generator.createElement(
                        'p',
                        '',
                        translationService.translate('generalSettings.cardsSettings.description')
                    )
                ]),
                generator.createElement('div', 'op-card-content', '', null, [
                    ...generator.createFormFields([
                        {
                            name: 'enable3ds',
                            value: activeCardsSettings.enable3ds,
                            type: 'checkbox',
                            className: '',
                            label: `generalSettings.cardsSettings.enable3ds.title`,
                            description: `generalSettings.cardsSettings.enable3ds.description`,
                            onChange: (value) => handleCardsSettingsChange('enable3ds', value)
                        },
                        {
                            name: 'enforceStrongAuthentication',
                            value: activeCardsSettings.enforceStrongAuthentication,
                            type: 'checkbox',
                            className: '',
                            label: `generalSettings.cardsSettings.enforceStrongAuthentication.title`,
                            description: `generalSettings.cardsSettings.enforceStrongAuthentication.description`,
                            onChange: (value) => handleCardsSettingsChange('enforceStrongAuthentication', value)
                        },
                        {
                            name: 'enable3dsExemption',
                            value: activeCardsSettings.enable3dsExemption,
                            type: 'checkbox',
                            className: '',
                            label: `generalSettings.cardsSettings.3dsExemption.title`,
                            description: `generalSettings.cardsSettings.3dsExemption.description`,
                            onChange: (value) => handleCardsSettingsChange('enable3dsExemption', value)
                        }
                    ]),
                    generator.createDropdownField({
                        name: 'exemptionType',
                        value: activeCardsSettings.exemptionType,
                        label: 'generalSettings.cardsSettings.exemptionType.title',
                        description: 'generalSettings.cardsSettings.exemptionType.description',
                        options: [
                            {
                                label: 'generalSettings.cardsSettings.exemptionType.values.lowValue',
                                value: 'low-value'
                            },
                            {
                                label: 'generalSettings.cardsSettings.exemptionType.values.transactionRisk',
                                value: 'transaction-risk-analysis'
                            }
                        ],
                        onChange: (value) => handleCardsSettingsChange('exemptionType', value)
                    }),
                    generator.createNumberField({
                        name: 'exemptionLimit',
                        value: activeCardsSettings.exemptionLimit,
                        label: 'generalSettings.cardsSettings.exemptionLimit.title',
                        description: 'generalSettings.cardsSettings.exemptionLimit.description',
                        onChange: (value) => handleCardsSettingsChange('exemptionLimit', value)
                    }),
                    buttonWrapper
                ])
            ]);

            return cardsForm;
        }

        const handleDependencies = (prop, value) => {
            if (prop === 'enable3ds') {
                let enforceStrongAuthentication = utilities.getAncestor(document.querySelector(
                        '[name="enforceStrongAuthentication"]'), 'op-field-wrapper'),
                    enableExemption = utilities.getAncestor(
                        document.querySelector('[name="enable3dsExemption"]'),
                        'op-field-wrapper'
                    );
                if (value === true) {
                    utilities.showElement(enforceStrongAuthentication);
                    utilities.showElement(enableExemption);
                } else {
                    utilities.hideElement(enforceStrongAuthentication);
                    utilities.hideElement(enableExemption);
                }
            }

            if (prop === 'enforceStrongAuthentication') {
                let enableExemption = utilities.getAncestor(
                    document.querySelector('[name="enable3dsExemption"]'),
                    'op-field-wrapper'
                );
                if (value === true) {
                    utilities.hideElement(enableExemption);
                } else {
                    utilities.showElement(enableExemption);
                }
            }

            if (prop === 'enable3dsExemption') {
                let exemptionType = utilities.getAncestor(
                        document.querySelector('[name="exemptionType"]'),
                        'op-field-wrapper'
                    ),
                    exemptionLimit = utilities.getAncestor(
                        document.querySelector('[name="exemptionLimit"]'),
                        'op-field-wrapper'
                    );
                if (value === true) {
                    utilities.showElement(exemptionType);
                    utilities.showElement(exemptionLimit);
                } else {
                    utilities.hideElement(exemptionType);
                    utilities.hideElement(exemptionLimit);
                }
            }
        }

        const renderLogForm = () => {
            if (logForm) {
                templateService.clearComponent(logForm);
            }

            let saveButton = generator.createButton({
                    type: 'primary',
                    name: 'logSettingsBtn',
                    disabled: true,
                    label: 'general.saveChanges',
                    onClick: () => {
                        let logDays = logForm.querySelector('[name="logDays"]');

                        let isValid = validator.validateRequiredField(logDays);

                        if (isValid) {
                            utilities.showLoader();
                            api.post(configuration.saveLogSettingsUrl, changedLogSettings)
                                .then(() => handleSaveSuccess('logSettings'))
                                .catch(() => handleSaveFailure('logSettings'))
                                .finally(() => {
                                    utilities.hideLoader();
                                });
                        }
                    }
                }
            );
            const buttonWrapper = generator.createElement('div', 'op-button-wrapper');
            buttonWrapper.append(saveButton);

            logForm = generator.createElement('div', 'op-card', '', null, [
                    generator.createElement('div', 'op-card-title', '', null, [
                        generator.createElement(
                            'h1',
                            '',
                            translationService.translate('generalSettings.logSettings.title')
                        ),
                        generator.createElement(
                            'p',
                            '',
                            translationService.translate('generalSettings.logSettings.description')
                        )
                    ]),
                    generator.createElement('div', 'op-card-content', '', null, [
                        ...generator.createFormFields([
                                {
                                    name: 'debugMode',
                                    value: activeLogSettings.debugMode,
                                    type: 'checkbox',
                                    className: '',
                                    label: `generalSettings.logSettings.debugMode.title`,
                                    description: `generalSettings.logSettings.debugMode.description`,
                                    onChange: (value) => handleLogSettingsChange('debugMode', value)
                                },
                                {
                                    name: 'logDays',
                                    value: activeLogSettings.logDays,
                                    type: 'number',
                                    className: '',
                                    label: `generalSettings.logSettings.logDays.title`,
                                    description: `generalSettings.logSettings.logDays.description`,
                                    onChange: (value) => handleLogSettingsChange('logDays', value)
                                }
                            ]
                        ),
                        buttonWrapper
                    ])
                ]
            );

            return logForm;
        }

        const renderPayByLinkForm = () => {
            if (payByLinkForm) {
                templateService.clearComponent(payByLinkForm);
            }

            let payByLinkBtn = generator.createButton({
                type: 'primary',
                name: 'payByLinkBtn',
                disabled: true,
                label: 'general.saveChanges',
                onClick: () => {
                    utilities.showLoader();
                    api.post(configuration.savePayByLinkSettingsUrl, changedPayByLinkSettings)
                        .then(() => handleSaveSuccess('payByLinkSettings'))
                        .catch(() => handleSaveFailure('payByLinkSettings'))
                        .finally(() => {
                            utilities.hideLoader();
                        });
                }
            });
            const buttonWrapper = generator.createElement('div', 'op-button-wrapper');
            buttonWrapper.append(payByLinkBtn);

            payByLinkForm = generator.createElement('div', 'op-card', '', null, [
                    generator.createElement('div', 'op-card-title', '', null, [
                        generator.createElement(
                            'h1',
                            '',
                            translationService.translate('generalSettings.payByLinkSettings.title')
                        ),
                        generator.createElement(
                            'p',
                            '',
                            translationService.translate('generalSettings.payByLinkSettings.description')
                        )
                    ]),
                    generator.createElement('div', 'op-card-content', '', null, [
                        ...generator.createFormFields([
                                {
                                    name: 'enabled',
                                    value: activePayByLinkSettings.enabled,
                                    type: 'checkbox',
                                    className: '',
                                    label: `generalSettings.payByLinkSettings.enable.title`,
                                    description: OnlinePaymentsFE.brand.code + `.generalSettings.payByLinkSettings.enable.description`,
                                    onChange: (value) => handlePayByLinkSettingsChange('enabled', value)
                                },
                                {
                                    name: 'title',
                                    value: activePayByLinkSettings.title,
                                    type: 'text',
                                    className: '',
                                    label: `generalSettings.payByLinkSettings.payByLinkTitle.title`,
                                    description: `generalSettings.payByLinkSettings.payByLinkTitle.description`,
                                    onChange: (value) => handlePayByLinkSettingsChange('title', value)
                                },
                                {
                                    name: 'expirationTime',
                                    value: activePayByLinkSettings.expirationTime,
                                    type: 'number',
                                    className: '',
                                    label: `generalSettings.payByLinkSettings.expirationTime.title`,
                                    description: `generalSettings.payByLinkSettings.expirationTime.description`,
                                    onChange: (value) => handlePayByLinkSettingsChange('expirationTime', value)
                                }
                            ]
                        ),
                        buttonWrapper
                    ])
                ]
            );

            return payByLinkForm;
        }

        const handlePayByLinkSettingsChange = (prop, value) => {
            let payByLinkBtn = payByLinkForm.querySelector('[name="payByLinkBtn"]');
            if (prop === 'expirationTime') {
                let expirationTime = payByLinkForm.querySelector('[name="expirationTime"]');
                if (value < 0 || value > 180) {
                    validator.setError(expirationTime, 'generalSettings.payByLinkSettings.expirationTime.error');
                    payByLinkBtn.disabled = true;

                    return;
                } else {
                    validator.removeError(expirationTime);
                }
            }

            changedPayByLinkSettings[prop] = value;

            handlePayByLinkDependencies(prop, value);

            payByLinkBtn.disabled = changedPayByLinkSettings.enabled === activePayByLinkSettings.enabled &&
                changedPayByLinkSettings.title === activePayByLinkSettings.title &&
                changedPayByLinkSettings.expirationTime === activePayByLinkSettings.expirationTime;
        }

        const handlePayByLinkDependencies = (prop, value) => {
            if (prop === 'enabled') {
                let title = utilities.getAncestor(payByLinkForm.querySelector(
                        '[name="title"]'), 'op-field-wrapper'),
                    expirationTime = utilities.getAncestor(
                        payByLinkForm.querySelector('[name="expirationTime"]'),
                        'op-field-wrapper'
                    );
                if (value === true) {
                    utilities.showElement(title);
                    utilities.showElement(expirationTime);
                } else {
                    utilities.hideElement(title);
                    utilities.hideElement(expirationTime);
                }
            }
        }

        const renderDisconnectForm = () => {
            if (disconnectForm) {
                templateService.clearComponent(disconnectForm);
            }

            let disconnectBtn = generator.createButton({
                    type: 'primary',
                    name: 'saveButton',
                    className: 'opm--destructive',
                    disabled: false,
                    label: 'generalSettings.disconnect.disconnect',
                    onClick: () => {
                        utilities.showLoader();
                        api.get(configuration.disconnectUrl).then((response) => {
                                OnlinePaymentsFE.state.display();
                            }
                        )
                            .finally(() => {
                                utilities.hideLoader();
                            });
                    }
                }
            );
            const buttonWrapper = generator.createElement('div', 'op-button-wrapper');
            buttonWrapper.append(disconnectBtn);


            disconnectForm = generator.createElement('div', 'op-card', '', null, [
                generator.createElement('div', 'op-card-title', '', null, [
                    generator.createElement(
                        'h1',
                        '',
                        translationService.translate('generalSettings.disconnect.title')
                    ),
                    generator.createElement(
                        'p',
                        '',
                        translationService.translate(OnlinePaymentsFE.brand.code + '.generalSettings.disconnect.description')
                    )
                ]),
                generator.createElement('div', 'op-card-content', '', null, [
                    generator.createElement(
                        'p',
                        '',
                        translationService.translate('generalSettings.disconnect.warning')
                    ),
                    buttonWrapper
                ])
            ]);

            return disconnectForm;
        }

        const handleCardsSettingsChange = (prop, value) => {
            changedCardsSettings[prop] = value;
            let cardSettingsBtn = cardsForm.querySelector('[name="cardSettingsBtn"]');

            cardSettingsBtn.disabled = changedCardsSettings.enable3ds === activeCardsSettings.enable3ds &&
                changedCardsSettings.enforceStrongAuthentication === activeCardsSettings.enforceStrongAuthentication &&
                changedCardsSettings.enable3dsExemption === activeCardsSettings.enable3dsExemption &&
                changedCardsSettings.exemptionType === activeCardsSettings.exemptionType &&
                changedCardsSettings.exemptionLimit === activeCardsSettings.exemptionLimit;

            handleDependencies(prop, value);
        }

        const handleLogSettingsChange = (prop, value) => {
            let logBtn = logForm.querySelector('[name="logSettingsBtn"]');
            if (prop === 'logDays') {
                let days = logForm.querySelector('[name="logDays"]');
                if (value < 1 || value > 14) {
                    validator.setError(days, 'generalSettings.logSettings.logDays.error');
                    logBtn.disabled = true;

                    return;
                } else {
                    validator.removeError(days);
                }
            }

            changedLogSettings[prop] = value;

            logBtn.disabled = changedLogSettings.logDays === activeLogSettings.logDays &&
                changedLogSettings.debugMode === activeLogSettings.debugMode;
        }

        const handleSaveSuccess = (section) => {
            showFlashMessage('generalSettings.' + section + '.message', 'success');
            templateService.clearMainPage();
            renderPage().then(r => {
            });
        }

        const handleSaveFailure = (section) => {
            showFlashMessage('generalSettings.' + section + '.error', 'error');
        }

        /**
         * Displays the flash message.
         *
         * @param {string} message Translation key or message
         * @param {'success' | 'error'} status
         */
        const showFlashMessage = (message, status = 'success') => {
            const container = templateService.getMainPage()?.querySelector('.op-flash-message-wrapper');
            if (!container) {
                return;
            }

            templateService.clearComponent(container);
            container.append(utilities.createFlashMessage(message, status));
            container.scrollIntoView({behavior: 'smooth'});
        };
    }

    OnlinePaymentsFE.SettingsController = SettingsController;
})();
