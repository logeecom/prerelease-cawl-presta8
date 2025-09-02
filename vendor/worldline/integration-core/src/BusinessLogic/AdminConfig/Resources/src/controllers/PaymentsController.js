if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

(function () {
    /**
     * @typedef TranslatableLabel
     *
     * @property {string}
     */

    /**
     * @typedef PaymentMethod
     * @property {string} paymentProductId
     * @property {TranslatableLabel[]} name
     * @property {string} paymentGroup
     * @property {string[]} integrationTypes
     * @property {boolean} enabled
     */

    const paymentMethodsLearnMoreLinks = {
        cards: 'index?payment-method=Cards%20%28debit%20%26%20credit%29',
        hosted_checkout: '',
        5405: 'alipay',
        302: 'applepay',
        5408: 'bank-transfer',
        3103: 'bimpli-cado',
        5001: 'bizum',
        5601: 'cadhoc',
        5403: 'cheque-vacances-connect',
        5133: 'cetelem-3x-4x',
        5129: 'cofidis-3x-4x',
        5100: 'cpay',
        2: 'american-express',
        3012: 'bancontact',
        130: 'cartes-bancaires',
        132: 'diners',
        128: 'discover',
        125: 'jcb',
        3: 'mastercard',
        117: 'maestro',
        56: 'union-pay',
        1: 'visa',
        5406: 'eps',
        320: 'google-pay',
        809: 'ideal',
        3112: 'illicado',
        5700: 'intersolve',
        3301: 'klarna',
        5908: 'mb-way',
        5402: 'mealvouchers',
        5500: 'multibanco',
        5111: 'oney-3x-4x',
        5112: 'oney-3x-4x',
        5127: 'oney-bank-card',
        5125: 'oney-financement-long',
        5600: 'oneybrandedgiftcard',
        3124: 'przelewy24',
        840: 'paypal',
        3203: 'postfinance-pay',
        771: 'sepa-direct-debit',
        5131: 'sofinco-3x-4x',
        3116: 'spirit-of-cadeau',
        5407: 'twint',
        5404: 'wechat'
    };

    const paymentGroups = [
        'mobile',
        'realTimeBanking',
        'giftCards',
        'prepaid',
        'instalment',
        'cards',
        'eWallet',
        'postpaid',
        'directDebit',
        'hosted'
    ];

    const integrationTypes = [
        'hosted',
        'redirect',
        'tokenization'
    ];

    /**
     * @typedef AdditionalDataConfig
     * @property {boolean?} showLogos
     * @property {boolean?} singleClickPayment
     * @property {boolean?} sendBasket
     * @property {boolean?} installments
     * @property {boolean?} installmentAmounts
     * @property {string[]?} installmentCountries
     * @property {string?} supportedInstallments
     * @property {number?} minimumAmount
     * @property {string?} numberOfInstallments
     * @property {string?} bankIssuer
     * @property {string?} merchantId
     * @property {string?} publicKeyId
     * @property {string?} storeId
     * @property {string?} gatewayMerchantId
     * @property {string?} merchantName
     * @property {boolean?} displayButtonOn
     */

    /**
     * @typedef PaymentMethodConfiguration
     * @property {boolean} isNew
     * @property {boolean} excludeFromPayByLink
     * @property {string} methodId
     * @property {string} code
     * @property {string?} name
     * @property {string?} description
     * @property { 'none' | 'fixed' | 'percent' | 'combined' } surchargeType
     * @property {number?} fixedSurcharge
     * @property {number?} percentSurcharge
     * @property {number?} surchargeLimit
     * @property {string?} logo
     * @property {Blob?} logoFile
     * @property {'creditOrDebitCard' | 'buyNowPayLater' | 'cashOrAtm' | 'directDebit' | 'onlinePayments' | 'wallet' |
     *     'prepaidAndGiftCard' | 'mobile'} paymentType
     * @property {AdditionalDataConfig?} additionalData
     */
    /**
     * Handles payments pages logic.
     *
     * @param {{getAvailablePaymentsUrl: string, enableMethodUrl: string,
     *     saveMethodConfigurationUrl: string, getMethodConfigurationUrl: string,
     *     paymentMethodLogoUrl: string, getLanguagesUrl: string
     *     }} configuration
     * @constructor
     */
    function PaymentsController(configuration) {
        /** @type AjaxServiceType */
        const api = OnlinePaymentsFE.ajaxService;

        const {
            templateService,
            translationService,
            elementGenerator: generator,
            validationService: validator,
            components,
            utilities
        } = OnlinePaymentsFE;

        const dataTableComponent = OnlinePaymentsFE.components.DataTable;

        /** @type {HTMLElement} */
        let page;

        /** @type {Record<string, string[]>} */
        let activeFilters = {};

        /** @type {PaymentMethod[]} */
        let availableMethods = [];

        /** @type {number} */
        let numberOfChanges = 0;

        /**
         * Replaces an active page with the other one rendered by a provider renderer method.
         *
         * @param {() => void} renderer
         */
        const switchPage = (renderer) => {
            utilities.showLoader();
            document.querySelector('.op-form-footer')?.remove();
            if (!page) {
                page = generator.createElement('div', 'op-payments-page');
            } else {
                templateService.clearComponent(page);
            }

            activeFilters = {};
            renderer();
        };

        /**
         * Filters methods based on the current filter.
         *
         * @returns {PaymentMethod[]}
         */
        const applyFilter = () => {
            return availableMethods.filter((method) => {
                if (activeFilters.integrationType?.length && !activeFilters.integrationType.some(item => method.integrationTypes.includes(
                    item))) {
                    return false;
                }

                if (
                    activeFilters.paymentGroups?.length &&
                    !activeFilters.paymentGroups.includes(method.paymentGroup)
                ) {
                    return false;
                }

                if (activeFilters.paymentProduct?.length &&
                    !method.name.value.toLowerCase().includes(activeFilters.paymentProduct)) {
                    return false;
                }

                return true;
            });
        };

        function renderPaymentMethodsList(form, methods) {
            const methodsList = generator.createElement('div');

            methods.forEach((method) => {
                const wrapper = generator.createElement('div', 'op-payment-component');
                const logo = generator.createElement('img', 'op-payment-logo');
                logo.src = configuration.paymentMethodLogoUrl + method.paymentProductId + '.svg';
                wrapper.appendChild(logo);
                const methodDetails = generator.createElement('div', 'op-payment-details-wrapper');
                const methodNameWrapper = generator.createElement('div', 'op-payment-details');
                const methodName = generator.createElement(
                    'p',
                    'op-payment-name',
                    OnlinePaymentsFE.sanitize(translationService.translate(method.name.value))
                );
                methodNameWrapper.appendChild(methodName);
                method.integrationTypes.forEach((type) => {
                    const typeElement = generator.createElement(
                        'span',
                        'op-integration-type ' + 'op-' + type,
                        'payments.integrationType.' + type
                    );
                    methodNameWrapper.appendChild(typeElement);
                });
                methodDetails.appendChild(methodNameWrapper);
                wrapper.appendChild(methodDetails);
                let paymentGroup = generator.createElement(
                    'p',
                    'op-payment-group',
                    'payments.paymentGroups.' + method.paymentGroup
                );

                if (method.paymentGroup === 'hosted') {
                    paymentGroup = generator.createElement(
                        'p',
                        'op-payment-group',
                        OnlinePaymentsFE.brand.code + '.payments.paymentGroups.' + method.paymentGroup
                    );
                }

                methodDetails.appendChild(paymentGroup);
                const actions = generator.createElement('div', 'op-more-actions');
                const toggleWrapper = generator.createElement('div', 'opp-field-component');
                const toggleLabel = generator.createElement('label', 'op-toggle');
                const toggleInput = generator.createElement(
                    'input',
                    'opp-toggle-input',
                    '',
                    { 'type': 'checkbox', 'checked': method.enabled }
                );
                toggleInput.addEventListener('click', function (event) {
                    utilities.showLoader();
                    const url = configuration.enableMethodUrl;
                    const data = {
                        paymentProductId: method.paymentProductId,
                        enabled: !method.enabled
                    }

                    api.post(url, data).then((response) => {
                        utilities.hideLoader();
                    }).catch((errorResponse) => {
                        this.checked = method.enabled;
                        utilities.hideLoader();
                    });
                });
                const toggleSpan = generator.createElement('span', 'opp-toggle-round');
                toggleLabel.appendChild(toggleInput);
                toggleLabel.appendChild(toggleSpan);
                toggleWrapper.appendChild(toggleLabel);
                actions.appendChild(toggleWrapper);
                const moreActionsWrapper = generator.createElement('div', 'op-link-dropdown');
                const moreActions = generator.createElement('div', 'op-list-dropdown');
                const moreActionsButton = generator.createElement(
                    'button',
                    'op-more-actions-button opt--ghost',
                    '\u00B7\u00B7\u00B7'
                );
                moreActionsButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    moreActionsList.classList.toggle('ops--show');
                    wrapper.classList.toggle('ops--active');
                });

                const moreActionsList = generator.createElement('ul', 'opp-dropdown-list');
                const settings = generator.createElement(
                    'li',
                    'opp-dropdown-list-item-icon-before op-settings',
                    'payments.list.settings'
                );
                const learnMore = generator.createElement(
                    'li',
                    'opp-dropdown-list-item-icon-before op-learn-more',
                    'payments.list.learnMore'
                );
                settings.addEventListener('click', (event) => {
                    moreActionsList.classList.toggle('ops--show');
                    api.get(configuration.getMethodConfigurationUrl.replace(
                            '{methodId}',
                            method.paymentProductId
                        )
                    ).then((response) => {
                        api.get(configuration.getLanguagesUrl).then((languages) => {
                            templateService.getMainPage().appendChild(generator.createElement(
                                'div',
                                'op-dark-mask'
                            ));

                            const modal = OnlinePaymentsFE.components.SlidingModal.create(
                                {
                                    paymentProductId: method.paymentProductId,
                                    enabled: response.enabled,
                                    name: response.name,
                                    integrationTypes: method.integrationTypes,
                                    paymentGroup: method.paymentGroup,
                                    templateName: response.template,
                                    paymentAction: response.paymentAction,
                                    logo: configuration.paymentMethodLogoUrl + method.paymentProductId + '.svg',
                                    additionalData: response.additionalData
                                },
                                configuration.saveMethodConfigurationUrl,
                                languages
                            );
                            page.appendChild(modal);

                            setTimeout(() => {
                                modal.classList.add('op-open');
                            }, 200);
                        })
                    })
                });

                learnMore.addEventListener('click', (event) => {
                    moreActionsList.classList.toggle('ops--show');
                    window.open(translationService.translate(OnlinePaymentsFE.brand.code + '.documentationLink')
                        + paymentMethodsLearnMoreLinks[method.paymentProductId], '_blank');
                });
                document.addEventListener('mouseup', function (e) {
                    if (!moreActionsList.contains(e.target)) {
                        moreActionsList.classList.remove('ops--show');
                    }
                });

                moreActionsList.appendChild(settings);
                moreActionsList.appendChild(learnMore);
                moreActions.appendChild(moreActionsButton);
                moreActions.appendChild(moreActionsList);
                moreActionsWrapper.appendChild(moreActions);
                actions.appendChild(moreActionsWrapper);
                wrapper.appendChild(actions);

                methodsList.appendChild(wrapper);
            });

            form.appendChild(renderPaymentsTableFilter());
            form.appendChild(methodsList);
            page.appendChild(form);

            templateService.getMainPage().append(page);
        }

        /**
         * Renders the active payments form.
         */
        const renderActivePaymentsForm = () => {
            const form = generator.createElement('form', 'op-payments-form');
            const title = generator.createElement('p', 'op-payments-title');
            const divider = generator.createElement('div', 'op-payments-divider');

            title.innerHTML = translationService.translate('payments.title');

            form.appendChild(title);
            form.appendChild(divider);

            api.get(configuration.getAvailablePaymentsUrl)
                .then((methods) => {
                    templateService.clearMainPage();
                    availableMethods = methods;
                    renderPaymentMethodsList(form, methods);
                })
                .catch(() => false)
                .finally(() => {
                    let header = templateService.getHeaderSection();
                    let title = header.querySelector('.op-main-title');
                    title.innerText = OnlinePaymentsFE.sanitize(translationService.translate('payments.pageTitle'));
                    utilities.hideLoader();
                    OnlinePaymentsFE.state.setHeader();
                    OnlinePaymentsFE.state.initializeFooter();
                });
        };

        /**
         * Creates payments table filer.
         */
        const renderPaymentsTableFilter = () => {
            let filters = generator.createElement('div', 'opp-table-filters');
            let title = generator.createElement(
                'div',
                'op-title',
                '',
                '',
                [
                    generator.createElement(
                        'span',
                        'op-filter-icon'
                    ),
                    generator.createElement(
                        'p',
                        '',
                        translationService.translate('payments.filter.filter')
                    ),
                    generator.createElement(
                        'div',
                        'op-vertical-divider'
                    )
                ]
            );
            let resetBtn = generator.createButton(
                {
                    type: 'ghost',
                    size: 'small',
                    className: 'opm--icon',
                    label: 'payments.filter.resetAll',
                    onClick: () => {
                        activeFilters = {};
                        let form = document.querySelector('.op-payments-form');
                        templateService.clearComponent(form);

                        renderPaymentMethodsList(form, applyFilter());
                    }
                }
            );
            let container = generator.createElement('div', 'opp-table-filter-wrapper', '', null, [
                filters
            ]);

            const changeFilter = (filter, values) => {
                activeFilters[filter] = values;
                resetBtn.disabled =
                    Object.values(activeFilters).reduce((result, options) => result + options.length, 0) === 0;
                let form = document.querySelector('.op-payments-form');
                templateService.clearComponent(form);

                renderPaymentMethodsList(form, applyFilter());
            };
            let searchFiled = generator.createElement(
                'input',
                '',
                activeFilters.paymentProduct || translationService.translate('payments.filter.paymentProduct.label'),
                {
                    placeholder: translationService.translate('payments.filter.paymentProduct.placeholder'),
                    onChange: (event) => changeFilter('paymentProduct', event.target.value)
                }
            );

            if (activeFilters.paymentProduct) {
                searchFiled.value = activeFilters.paymentProduct;
            }

            filters.append(
                ...[
                    title,
                    components.TableFilter.create({
                        name: 'integrationType',
                        isMultiselect: true,
                        label: translationService.translate('payments.filter.types.label'),
                        values: activeFilters.integrationType || [],
                        options: [
                            { label: translationService.translate('payments.integrationType.hosted'), value: 'hosted' },
                            {
                                label: translationService.translate('payments.integrationType.redirect'),
                                value: 'redirect'
                            },
                            {
                                label: translationService.translate('payments.integrationType.tokenization'),
                                value: 'tokenization'
                            }
                        ],
                        selectPlaceholder: 'payments.filter.types.selectPlaceholder',
                        onChange: (values) => changeFilter('integrationType', values)
                    }),
                    components.TableFilter.create({
                        name: 'paymentGroups',
                        isMultiselect: true,
                        label: translationService.translate('payments.filter.paymentGroups.label'),
                        values: activeFilters.paymentGroups || [],
                        options: [
                            { label: translationService.translate('payments.paymentGroups.mobile'), value: 'mobile' },
                            {
                                label: translationService.translate('payments.paymentGroups.realTimeBanking'),
                                value: 'realTimeBanking'
                            },
                            {
                                label: translationService.translate('payments.paymentGroups.giftCards'),
                                value: 'giftCards'
                            },
                            { label: translationService.translate('payments.paymentGroups.prepaid'), value: 'prepaid' },
                            {
                                label: translationService.translate('payments.paymentGroups.instalment'),
                                value: 'instalment'
                            },
                            { label: translationService.translate('payments.paymentGroups.cards'), value: 'cards' },
                            { label: translationService.translate('payments.paymentGroups.eWallet'), value: 'eWallet' },
                            {
                                label: translationService.translate('payments.paymentGroups.postpaid'),
                                value: 'postpaid'
                            },
                            {
                                label: translationService.translate('payments.paymentGroups.directDebit'),
                                value: 'directDebit'
                            },
                            {
                                label: translationService.translate(OnlinePaymentsFE.brand.code + '.payments.paymentGroups.hosted'),
                                value: 'hosted'
                            },
                        ],
                        selectPlaceholder: 'payments.filter.paymentGroups.selectPlaceholder',
                        onChange: (values) => changeFilter('paymentGroups', values)
                    }),
                    searchFiled,
                    resetBtn
                ]
            );

            return container;
        };

        /**
         * Displays page content.
         *
         * @param {{ storeId: string }} config
         */
        this.display = ({ storeId }) => {
            configuration.getAvailablePaymentsUrl = configuration.getAvailablePaymentsUrl.replace('{storeId}', storeId);
            configuration.enableMethodUrl = configuration.enableMethodUrl.replace(
                '{storeId}',
                storeId
            );
            configuration.saveMethodConfigurationUrl = configuration.saveMethodConfigurationUrl.replace(
                '{storeId}',
                storeId
            );
            configuration.getMethodConfigurationUrl = configuration.getMethodConfigurationUrl.replace(
                '{storeId}',
                storeId
            );
            configuration.getLanguagesUrl = configuration.getLanguagesUrl.replace(
                '{storeId}',
                storeId
            );
            switchPage(renderActivePaymentsForm);
        };

        /**
         * Sets the unsaved changes.
         *
         * @return {boolean}
         */
        this.hasUnsavedChanges = () => {
            if (numberOfChanges > 0) {
                return true;
            }
        };
    }

    OnlinePaymentsFE.PaymentsController = PaymentsController;
})();
