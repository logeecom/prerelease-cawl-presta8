if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

(function () {
    /**
     * @typedef Store
     * @property {string} storeId
     * @property {string} storeName
     */
    /**
     * @typedef Merchant
     * @property {string} merchantId
     * @property {string} merchantName
     */

    /**
     * @typedef StateConfiguration
     * @property {string?} pagePlaceholder
     * @property {string} stateUrl
     * @property {string} storesUrl
     * @property {string} currentStoreUrl
     * @property {string} connectionDetailsUrl
     * @property {string} versionUrl
     * @property {string} downloadVersionUrl
     * @property {Object} brand
     * @property {string?} systemId
     * @property {Record<string, any>} pageConfiguration
     * @property {Record<string, any>} templates
     */

    /**
     * Main controller of the application.
     *
     * @param {StateConfiguration} configuration
     *
     * @constructor
     */
    function StateController(configuration) {
        /** @type AjaxServiceType */
        const api = OnlinePaymentsFE.ajaxService;

        const {
            pageControllerFactory,
            utilities,
            templateService,
            elementGenerator,
            translationService
        } = OnlinePaymentsFE;

        let currentState = '';
        let previousState = '';
        let controller = null;

        /**
         * Main entry point for the application.
         * Determines the current state and runs the start controller.
         */
        this.display = () => {
            utilities.showLoader();
            templateService.setTemplates(configuration.templates);

            window.addEventListener('hashchange', updateStateOnHashChange, false);

            api.get(configuration.currentStoreUrl, () => null, true)
                .then(
                    /** @param {Store|Store[]} response */
                    (response) => {
                        const loadStore = (store) => {
                            setStoreId(store.storeId);

                            return Promise.all([displayPageBasedOnState()]);
                        };

                        let store;
                        if (!Array.isArray(response)) {
                            store = response;
                        } else {
                            store = response.find((s) => s.storeId === getStoreId());
                        }

                        if (!store) {
                            // the active store is probably deleted, we need to switch to the default store
                            return api.get(configuration.currentStoreUrl, null, true).then(loadStore);
                        }

                        return loadStore(store);
                    }
                )
                .catch(() => {
                    initializeHeader(configuration.brand.code);
                    this.initializeFooter();
                    return this.disableHeaderTabs();
                })
                .finally(() => {
                    utilities.hideLoader();
                });
        };

        /**
         * Navigates to a state.
         *
         * @param {string} state
         * @param {Record<string, any> | null?} additionalConfig
         * @param {boolean} [force=false]
         */
        this.goToState = (state, additionalConfig = null, force = false) => {
            if (currentState === state && !force) {
                return;
            }

            window.location.hash = state;

            const config = {
                storeId: getStoreId(),
                ...(additionalConfig || {})
            };

            const [controllerName, page, stateParam] = state.split('-');
            controller = pageControllerFactory.getInstance(
                controllerName,
                getControllerConfiguration(controllerName, page, stateParam)
            );

            if (controller) {
                controller.display(config);
            }

            previousState = currentState;
            currentState = state;
        };

        /**
         * Enables the header tabs.
         */
        this.enableHeaderTabs = () => {
            document
                .querySelectorAll('.op-main-header-right .op-header-tab')
                .forEach((item) => item.classList.remove('ops--disabled'));
        };

        /**
         * Disables the header tabs.
         */
        this.disableHeaderTabs = () => {
            document
                .querySelectorAll('.op-main-header-right .op-header-tab')
                .forEach((item) => item.classList.add('ops--disabled'));
        };

        const updateStateOnHashChange = () => {
            const state = window.location.hash.substring(1);
            if (state) {
                this.goToState(state);
                updateHeaderTabs();
            }

            getHeader().classList.remove('ops--menu-active');
        };

        /**
         * Selects active header tab based on the location hash.
         */
        const updateHeaderTabs = () => {
            const sidebar = getHeader();
            sidebar.querySelectorAll('.opp-menu-item a').forEach((el) => el.classList.remove('ops--active'));
            sidebar.querySelector(`[href="${location.hash}"]`)?.classList.add('ops--active');
        };

        /**
         * Gets the header DOM element.
         *
         * @returns {HTMLElement}
         */
        const getHeader = () => {
            return document.querySelector('#op-page #op-main-header');
        };

        /**
         * Renders a confirmation modal for a store change when there are unsaved changes.
         */
        const renderSwitchToStoreModal = () => {
            return new Promise((resolve) => {
                const modal = OnlinePaymentsFE.components.Modal.create({
                    title: 'payments.switchToStore.title',
                    content: [
                        OnlinePaymentsFE.elementGenerator.createElement(
                            'span',
                            '',
                            'payments.switchToStore.description'
                        )
                    ],
                    footer: true,
                    canClose: false,
                    buttons: [
                        {
                            type: 'secondary',
                            label: 'general.back',
                            onClick: () => {
                                modal.close();
                                resolve(false);
                            }
                        },
                        {
                            type: 'primary',
                            className: 'opt--primary',
                            label: 'general.yes',
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

        /**
         * Updates the main header.
         *
         * @returns {Promise<void>}
         */
        this.setHeader = () => {
            return setConnectionData();
        };

        /**
         * Updated the connection data in the main header.
         *
         * @returns {Promise<any>}
         */
        const setConnectionData = () => {
            return api
                .get(configuration.connectionDetailsUrl.replace('{storeId}', getStoreId()), () => null, true)
                .then(
                    /** @param {Connection} connection */
                    (connection) => {
                        if (!connection) {
                            return;
                        }

                        const modeElems = document.querySelectorAll('.op-status');

                        if (modeElems) {
                            modeElems.forEach((modeElem) => {
                                const stateElem = modeElem.querySelector('.op-mode');

                                if (stateElem) {
                                    templateService.clearComponent(stateElem);
                                }
                                templateService.clearComponent(modeElem);

                                modeElem.insertBefore(elementGenerator.createModeElement(connection.mode), modeElem.firstChild);
                            })
                        }
                    }
                );
        };

        /**
         * Initializes the header.
         */
        const initializeHeader = (brand, state) => {
            let existingHeader = getHeader();

            if (existingHeader) {
                templateService.clearComponent(existingHeader);
            }

            let name = translationService.translate('general.documentation');
            let options = [
                {
                    label: 'general.readyToGoLive',
                    link: translationService.translate(OnlinePaymentsFE.brand.code + '.links.readyToGoLive')
                },
                {
                    label: 'general.createAccount',
                    link: translationService.translate(OnlinePaymentsFE.brand.code + '.links.createAccount')
                },
                {
                    label: 'general.configurePlugin',
                    link: translationService.translate(OnlinePaymentsFE.brand.code + '.links.configurePlugin')
                },
                {
                    label: 'general.generalDocumentation',
                    link: translationService.translate(OnlinePaymentsFE.brand.code + '.links.generalDocumentation')
                }
            ];
            const header = elementGenerator.createHeaderItem(brand, {name, options}, state);
            let headerPlaceholder = document.querySelector('#op-main-header');
            headerPlaceholder.appendChild(header);

            updateHeaderTabs();
        };

        this.initializeFooter = () => {
            api.get(configuration.versionUrl, null, true)
                .then((version) => {
                    const footer = OnlinePaymentsFE.components.Footer.create({
                        newVersion: version.latest,
                        installedVersion: version.installed
                    });

                    const footerPlaceholder = document.querySelector('#op-footer');

                    templateService.clearComponent(footerPlaceholder);
                    footerPlaceholder.appendChild(footer);
                });
        };

        /**
         * Returns the current merchant state.
         *
         * @return {Promise<"connection" | "dashboard">}
         */
        this.getCurrentMerchantState = () => {
            return api
                .get(configuration.stateUrl.replace('{storeId}', getStoreId()), () => {
                })
                .then((response) => response?.state || 'connection');
        };

        /**
         * Opens a specific page based on the current state.
         */
        const displayPageBasedOnState = () => {
            return this.getCurrentMerchantState().then((state) => {
                // if user is logged in, go to payments
                switch (state) {
                    case 'connection':
                        initializeHeader(configuration.brand.code, state);
                        this.disableHeaderTabs();
                        this.goToState('connection', null, true);
                        this.initializeFooter();

                        break;
                    default:
                        initializeHeader(configuration.brand.code, state);
                        let hash = window.location.hash.substring(1);
                        let goTo = 'payments';

                        if (hash && hash !== 'connection') {
                            goTo = hash;
                        }

                        this.goToState(goTo, null, true);
                }
            });
        };

        /**
         * Gets controller configuration.
         *
         * @param {string} controllerName
         * @param {string?} page
         * @param {string?} stateParam
         * @return {Record<string, any>}}
         */
        const getControllerConfiguration = (controllerName, page, stateParam) => {
            let config = utilities.cloneObject(configuration.pageConfiguration[controllerName] || {});

            page && (config.page = page);
            stateParam && (config.stateParam = stateParam);

            return config;
        };

        /**
         * Sets the store ID.
         *
         * @param {string} storeId
         */
        const setStoreId = (storeId) => {
            sessionStorage.setItem('op-active-store-id', storeId);
        };

        /**
         * Gets the store ID.
         *
         * @returns {string}
         */
        const getStoreId = () => {
            return sessionStorage.getItem('op-active-store-id');
        };

        /**
         * Replaces storeId in the url.
         *
         * @param {string} url
         * @returns {string}
         */
        this.formatUrl = (url) => {
            return url.replace('{storeId}', getStoreId());
        }
    }

    OnlinePaymentsFE.StateController = StateController;
})();
