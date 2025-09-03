if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

(function () {
    /**
     * @typedef MonitoringLogsData
     * @property {boolean} nextPageAvailable
     * @property {MonitoringLog[]} monitoringLogs
     * @property {int} beginning
     * @property {int} end
     * @property {int} numberOfItems
     */

    /**
     * @typedef MonitoringLog
     * @property {string} orderId
     * @property {string} paymentNumber
     * @property {string} logLevel
     * @property {string} message
     * @property {string} createdAt
     * @property {string} requestMethod
     * @property {string} requestEndpoint
     * @property {string} requestBody
     * @property {string} statusCode
     * @property {string} responseBody
     * @property {string} transactionLink
     * @property {string} orderLink
     */

    /**
     * @typedef WebhookLogsData
     * @property {boolean} nextPageAvailable
     * @property {WebhookLog[]} webhookLogs
     * @property {int} beginning
     * @property {int} end
     * @property {int} numberOfItems
     */

    /**
     * @typedef WebhookLog
     * @property {string} orderId
     * @property {string} paymentNumber
     * @property {string} paymentMethod
     * @property {string} status
     * @property {string} type
     * @property {string} createdAt
     * @property {string} statusCode
     * @property {string} webhookBody
     * @property {string} transactionLink
     * @property {string} orderLink
     */

    /**
     * Handles monitoring page logic.
     *
     * @param {{getMonitoringLogsUrl: string, getWebhookLogsUrl: string,
     * downloadMonitoringLogsUrl: string, downloadWebhookLogsUrl: string, page: string}} configuration
     * @constructor
     */
    function MonitoringController(configuration) {
        /** @type AjaxServiceType */
        const api = OnlinePaymentsFE.ajaxService;
        const {
            templateService,
            elementGenerator: generator,
            utilities,
            components,
            translationService
        } = OnlinePaymentsFE;
        const dataTableComponent = components.DataTable;
        /** @type string */
        let currentStoreId = '';
        let activeType = 'webhooks';
        let nextPageAvailable = true;
        let currentlyLoading = false;
        let page = 1;
        let limit = 10;

        /**
         * Displays page content.
         *
         * @param {{state?: string, storeId: string}} config
         */
        this.display = ({ storeId }) => {
            currentStoreId = storeId;
            templateService.clearMainPage();

            configuration.getMonitoringLogsUrl = configuration.getMonitoringLogsUrl.replace(
                '{storeId}',
                storeId
            );
            configuration.downloadMonitoringLogsUrl = configuration.downloadMonitoringLogsUrl.replace(
                '{storeId}',
                storeId
            );
            configuration.getWebhookLogsUrl = configuration.getWebhookLogsUrl.replace(
                '{storeId}',
                storeId
            );
            configuration.downloadWebhookLogsUrl = configuration.downloadWebhookLogsUrl.replace(
                '{storeId}',
                storeId
            );

            return renderPage();
        };

        const renderPage = () => {
            utilities.showLoader();
            let url;
            let renderer;

            templateService.clearMainPage();

            renderTabs();

            switch (configuration.page) {
                case 'webhooks':
                    url = `${configuration.getWebhookLogsUrl}&pageNumber=${page}&pageSize=${limit}`;
                    renderer = renderWebhookLogsTable;
                    break;
                case 'logs':
                    url = `${configuration.getMonitoringLogsUrl}&pageNumber=${page}&pageSize=${limit}`;
                    renderer = renderMonitoringLogsTable;
                    break;
            }

            return api
                .get(url, () => {
                })
                .then(renderer)
                .finally(() => {
                    let header = templateService.getHeaderSection();
                    let title = header.querySelector('.op-main-title');
                    title.innerText = OnlinePaymentsFE.sanitize(translationService.translate('monitoring.title'));
                    utilities.hideLoader();
                    OnlinePaymentsFE.state.setHeader();
                    OnlinePaymentsFE.state.initializeFooter();
                });
        };

        const renderTabs = () => {
            let tabs = generator.createElement('div', 'op-tab');
            let webhookClasses = 'op-tab-item' + (configuration.page === 'webhooks' ? ' op-active' : '');
            let monitoringClasses = 'op-tab-item' + (configuration.page === 'logs' ? ' op-active' : '');
            let webhooksTab = generator.createElement(
                'div',
                webhookClasses,
                translationService.translate('monitoring.webhooks.title')
            );
            let monitoringTab = generator.createElement(
                'div',
                monitoringClasses,
                translationService.translate('monitoring.logs.title')
            );

            webhooksTab.addEventListener('click', () => {
                utilities.showLoader();
                monitoringTab.classList.remove('op-active');
                webhooksTab.classList.add('op-active');
                activeType = 'webhooks';
                api.get(`${configuration.getWebhookLogsUrl}&pageNumber=${page}&pageSize=${limit}`, () => {
                    })
                    .then(renderWebhookLogsTable)
                    .finally(() => {
                        utilities.hideLoader();
                    })
            });

            monitoringTab.addEventListener('click', () => {
                utilities.showLoader();
                webhooksTab.classList.remove('op-active');
                monitoringTab.classList.add('op-active');
                activeType = 'logs';
                api.get(`${configuration.getMonitoringLogsUrl}&pageNumber=${page}&pageSize=${limit}`, () => {
                    })
                    .then(renderMonitoringLogsTable)
                    .finally(() => {
                        utilities.hideLoader();
                    })
            });

            tabs.appendChild(webhooksTab);
            tabs.appendChild(monitoringTab);
            templateService.getMainPage().appendChild(tabs);
        };

        /**
         * Renders the webhook logs table.
         *
         * @param {WebhookLogsData} webhookLogsPage
         */
        const renderWebhookLogsTable = (webhookLogsPage) => {
            const headers = [
                'monitoring.webhooks.webhookEventsLogs.orderId',
                'monitoring.webhooks.webhookEventsLogs.paymentNumber',
                'monitoring.webhooks.webhookEventsLogs.paymentMethod',
                'monitoring.webhooks.webhookEventsLogs.status',
                'monitoring.webhooks.webhookEventsLogs.type',
                'monitoring.webhooks.webhookEventsLogs.createdAt',
                'monitoring.webhooks.webhookEventsLogs.statusCode',
                ''
            ];

            createNotifications(headers, getRowsConfig, 'Webhooks', webhookLogsPage);
        };

        /**
         * Renders the monitoring logs table.
         *
         * @param {MonitoringLogsData} monitoringLogsPage
         */
        const renderMonitoringLogsTable = (monitoringLogsPage) => {
            const headers = [
                'monitoring.logs.monitoringLogs.orderId',
                'monitoring.logs.monitoringLogs.paymentNumber',
                'monitoring.logs.monitoringLogs.logLevel',
                'monitoring.logs.monitoringLogs.logMessage',
                'monitoring.logs.monitoringLogs.createdAt',
                ''
            ];
            createNotifications(headers, getMonitoringLogsRowsConfig, 'Logs', monitoringLogsPage);
        };

        /**
         * @param {WebhookLog} log Log.
         */
        const renderWebhookDetailsModal = (log) => {
            const modal = components.Modal.create({
                title: `monitoring.webhooks.modal.title`,
                className: 'op-webhook-notifications-modal',
                content: [
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.orderId'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.orderId)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.paymentNumber'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.paymentNumber)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.paymentMethod'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.paymentMethod)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.status'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.status)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.type'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.type)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.createdAt'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.createdAt)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.statusCode'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.statusCode)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.webhooks.modal.webhookBody'
                        ),
                        generator.createElement('span', 'op-webhook-details', log.webhookBody)
                    ]),
                ],
                footer: true,
                canClose: true,
                buttons: [
                    {
                        type: 'primary',
                        label: 'general.ok',
                        onClick: () => modal.close()
                    }
                ]
            });

            modal.open();
        };

        /**
         * @param {MonitoringLog} log
         */
        const renderLogDetailsModal = (log) => {
            const modal = components.Modal.create({
                title: `monitoring.logs.modal.title`,
                className: 'op-webhook-notifications-modal',
                content: [
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.orderId'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.orderId)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.paymentNumber'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.paymentNumber)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.logLevel'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.logLevel)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.logMessage'
                        ),
                        generator.createElement('span', 'op-webhook-details', log.message)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.createdAt'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.createdAt)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.requestMethod'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.requestMethod)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.requestEndpoint'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.requestEndpoint)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.requestBody'
                        ),
                        generator.createElement('span', 'op-webhook-details', log.requestBody)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.responseStatusCode'
                        ),
                        generator.createElement('span', 'opp-reason-text', log.statusCode)
                    ]),
                    generator.createElement('p', 'opp-reason', '', null, [
                        generator.createElement(
                            'span',
                            'opp-reason-title',
                            'monitoring.logs.modal.responseBody'
                        ),
                        generator.createElement('span', 'op-webhook-details', log.responseBody)
                    ]),
                ],
                footer: true,
                canClose: true,
                buttons: [
                    {
                        type: 'primary',
                        label: 'general.ok',
                        onClick: () => modal.close()
                    }
                ]
            });

            modal.open();
        }

        /**
         * Renders webhooks table rows.
         *
         * @param {WebhookLog[]} webhookLogs
         * @returns {TableCell[][]}
         */
        const getRowsConfig = (webhookLogs) => {
            return webhookLogs?.map((webhookLog) => {
                return [
                    {
                        renderer: (cell) => {
                            if (webhookLog.orderLink) {
                                let link = generator.createElement(
                                    'a',
                                    'opm--left-aligned opm--green-text opm--link',
                                    '',
                                    { href: webhookLog.orderLink, target: '_blank' },
                                    [
                                        generator.createElement(
                                            'span',
                                            '',
                                            webhookLog.orderId
                                        )
                                    ]
                                );

                                cell.append(link);
                            } else {
                                cell.append(
                                    generator.createElement(
                                        'span',
                                        '',
                                        webhookLog.orderId
                                    )
                                );
                            }
                        },
                        className: 'opm--left-aligned'
                    },
                    {
                        renderer: (cell) => {
                            if (webhookLog.transactionLink) {
                                let link = generator.createElement(
                                    'a',
                                    'opm--left-aligned opm--green-text opm--link',
                                    '',
                                    { href: webhookLog.transactionLink, target: '_blank' },
                                    [
                                        generator.createElement(
                                            'span',
                                            '',
                                            webhookLog.paymentNumber
                                        )
                                    ]
                                );

                                cell.append(link);
                            } else {
                                cell.append(
                                    generator.createElement(
                                        'span',
                                        '',
                                        webhookLog.paymentNumber
                                    )
                                );
                            }
                        },
                        className: 'opm--left-aligned'
                    },
                    {
                        label: webhookLog.paymentMethod,
                        className: 'opm--left-aligned'
                    },
                    {
                        renderer: (cell) =>
                            cell.append(
                                generator.createElement(
                                    'span',
                                    `opp-status opt--${webhookLog.status.toLowerCase()}`,
                                    translationService.translate(`monitoring.webhooks.status.${webhookLog.status}`)
                                )
                            ),
                        className: 'opm--left-aligned'
                    },
                    {
                        label: webhookLog.type,
                        className: 'opm--left-aligned'
                    },
                    {
                        label: webhookLog.createdAt,
                        className: 'opm--left-aligned'
                    },
                    {
                        label: webhookLog.statusCode,
                        className: 'opm--left-aligned'
                    },
                    {
                        renderer: (cell) => {
                            const moreActionsWrapper = generator.createElement('div', 'op-link-dropdown');
                            const moreActions = generator.createElement('div', 'op-list-dropdown');
                            const moreActionsList = generator.createElement('ul', 'opp-dropdown-list');
                            const moreActionsButton = generator.createElement(
                                'button',
                                'op-more-actions-button opt--ghost',
                                '\u00B7\u00B7\u00B7'
                            );
                            moreActionsButton.addEventListener('click', (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                moreActionsList.classList.toggle('ops--show');
                                cell.classList.toggle('ops--active');
                            });

                            const viewDetails = generator.createElement(
                                'li',
                                'opp-dropdown-list-item-icon-before op-learn-more',
                                translationService.translate('monitoring.webhooks.webhookEventsLogs.viewDetails')
                            );
                            moreActionsList.appendChild(viewDetails);
                            viewDetails.addEventListener('click', (event) => {
                                moreActionsList.classList.toggle('ops--show');
                                cell.classList.toggle('ops--active');
                                renderWebhookDetailsModal(webhookLog);
                            });
                            moreActions.appendChild(moreActionsButton)
                            moreActions.appendChild(moreActionsList);
                            moreActionsWrapper.appendChild(moreActions);
                            cell.append(moreActionsWrapper);
                        }
                    }
                ];
            });
        };

        /**
         * Renders monitoring logs table rows.
         *
         * @param {MonitoringLog[]} monitoringLogs
         * @returns {TableCell[][]}
         */
        const getMonitoringLogsRowsConfig = (monitoringLogs) => {
            return monitoringLogs?.map((monitoringLog) => {
                const options = {
                    day: 'numeric',
                    month: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: false
                };

                const formattedDateTime = new Date(monitoringLog.createdAt)
                    .toLocaleString('en-US', options)
                    .replace(/, /g, ' ')
                    .replace(/\//g, '-');

                return [
                    {
                        renderer: (cell) => {
                            if (monitoringLog.orderLink) {
                                let link = generator.createElement(
                                    'a',
                                    'opm--left-aligned opm--green-text opm--link',
                                    '',
                                    { href: monitoringLog.orderLink, target: '_blank' },
                                    [
                                        generator.createElement(
                                            'span',
                                            '',
                                            monitoringLog.orderId
                                        )
                                    ]
                                );

                                cell.append(link);
                            } else {
                                cell.append(
                                    generator.createElement(
                                        'span',
                                        '',
                                        monitoringLog.orderId
                                    )
                                )
                            }
                        },
                        className: 'opm--left-aligned'
                    },
                    {
                        renderer: (cell) => {
                            if (monitoringLog.transactionLink) {
                                let link = generator.createElement(
                                    'a',
                                    'opm--left-aligned opm--green-text opm--link',
                                    '',
                                    { href: monitoringLog.transactionLink, target: '_blank' },
                                    [
                                        generator.createElement(
                                            'span',
                                            '',
                                            monitoringLog.paymentNumber
                                        )
                                    ]
                                );

                                cell.append(link);
                            } else {
                                cell.append(
                                    generator.createElement(
                                        'span',
                                        '',
                                        monitoringLog.paymentNumber
                                    )
                                )
                            }
                        },
                        className: 'opm--left-aligned'
                    },
                    {
                        renderer: (cell) =>
                            cell.append(
                                generator.createElement(
                                    'span',
                                    `opp-status opt--${monitoringLog.logLevel.toLowerCase()}`,
                                    translationService.translate(`monitoring.logs.severity.${monitoringLog.logLevel}`)
                                )
                            ),
                        className: 'opm--left-aligned'
                    },
                    {
                        label: monitoringLog.message,
                        className: 'opm--left-aligned'
                    },
                    {
                        label: formattedDateTime,
                        className: 'opm--left-aligned'
                    },
                    {
                        renderer: (cell) => {
                            const moreActionsWrapper = generator.createElement('div', 'op-link-dropdown');
                            const moreActions = generator.createElement('div', 'op-list-dropdown');
                            const moreActionsList = generator.createElement('ul', 'opp-dropdown-list');
                            const moreActionsButton = generator.createElement(
                                'button',
                                'op-more-actions-button opt--ghost',
                                '\u00B7\u00B7\u00B7'
                            );
                            moreActionsButton.addEventListener('click', (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                moreActionsList.classList.toggle('ops--show');
                                cell.classList.toggle('ops--active');
                            });

                            const viewDetails = generator.createElement(
                                'li',
                                'opp-dropdown-list-item-icon-before op-learn-more',
                                translationService.translate('monitoring.webhooks.webhookEventsLogs.viewDetails')
                            );
                            moreActionsList.appendChild(viewDetails);
                            viewDetails.addEventListener('click', (event) => {
                                moreActionsList.classList.toggle('ops--show');
                                cell.classList.toggle('ops--active');
                                renderLogDetailsModal(monitoringLog);
                            });
                            moreActions.appendChild(moreActionsButton)
                            moreActions.appendChild(moreActionsList);
                            moreActionsWrapper.appendChild(moreActions);
                            cell.append(moreActionsWrapper);
                        }
                    }
                ];
            });
        };

        /**
         * Returns a function that renders a notifications table and handles pagination.
         *
         * @param {string[]} headers The table headers.
         * @param {(notifications: any[]) => TableCell[][]} getRowsConfig A function that maps logs to table rows.
         * @param {string} type The type of logs.
         * @param {WebhookLogsData | MonitoringLogsData} logsPage Webhook logs page.
         */
        const createNotifications = (headers, getRowsConfig, type, logsPage) => {
            const typeLc = type.toLowerCase();
            nextPageAvailable = logsPage.nextPageAvailable;

            if (activeType !== typeLc) {
                page = 1;
            }
            currentlyLoading = false;

            let component = templateService.getMainPage().querySelector('.op-notifications-page');

            if (component) {
                component.parentNode.removeChild(component);
            }

            const headerCells = headers.map((headerLabel) => ({
                label: headerLabel,
                className: 'opm--center-aligned'
            }));

            const rows = getRowsConfig(typeLc === 'webhooks' ? logsPage.webhookLogs : logsPage.monitoringLogs);
            const downloadBtn = generator.createElement('a', 'op-button opt--ghost ops--icon', 'monitoring.download');

            downloadBtn.href = typeLc === 'webhooks' ? configuration.downloadWebhookLogsUrl : configuration.downloadMonitoringLogsUrl;

            templateService
                .getMainPage()
                .append(
                    generator.createElement('div', `op-notifications-page`, '', null, [
                        generator.createElement('div', 'op-title', '', null, [
                            generator.createElement(
                                'p',
                                '',
                                OnlinePaymentsFE.brand.code + `.monitoring.${typeLc}.description`
                            ),
                            downloadBtn
                        ]),
                        renderTableHeading(logsPage, typeLc),
                        rows.length
                            ? dataTableComponent.createDataTable(headerCells, rows, `op-notifications-table`)
                            : dataTableComponent.createNoItemsMessage(`monitoring.${typeLc}.noLogsMessage`)
                    ])
                );
        };

        /**
         * @param {WebhookLogsData | MonitoringLogsData} logsPage Webhook logs page.
         * @param {string} type
         */
        const renderTableHeading = (logsPage, type) => {
            return generator.createElement('div', 'op-table-heading', '', null, [
                renderSearchField(type),
                renderPagination(logsPage, type)
            ]);
        };

        /**
         * @param {string} type
         * @returns {*|HTMLElement}
         */
        const renderSearchField = (type) => {
            let wrapper = generator.createElement('div', 'op-search-wrapper');
            let searchContainer = generator.createElement('div', 'op-search-container');
            let searchWrapper = generator.createElement('div', 'op-search-field-wrapper');
            let searchIcon = generator.createElement('span', 'op-search-icon');
            let searchField = generator.createElement('input', 'op-search-input');

            searchField.type = 'text';
            searchField.placeholder = translationService.translate('monitoring.search.placeholder');
            searchWrapper.appendChild(searchIcon);
            searchWrapper.appendChild(searchField);

            let button = generator.createButton({
                type: 'primary',
                label: translationService.translate('general.search')
            });

            button.addEventListener('click', function () {
                utilities.showLoader();
                let url = '';
                let renderer = null;
                let searchTerm = OnlinePaymentsFE.sanitize(searchField.value);

                switch (type) {
                    case 'webhooks':
                        url = `${configuration.getWebhookLogsUrl}&pageNumber=${page}&pageSize=${limit}&searchTerm=${searchTerm}`;
                        renderer = renderWebhookLogsTable;
                        break;
                    case 'logs':
                        url = `${configuration.getMonitoringLogsUrl}&pageNumber=${page}&pageSize=${limit}&searchTerm=${searchTerm}`;
                        renderer = renderMonitoringLogsTable;
                        break;
                }

                api
                    .get(url, () => {
                    })
                    .then(renderer)
                    .finally(() => {
                        utilities.hideLoader();
                    });
            });

            searchContainer.appendChild(searchWrapper);
            searchContainer.appendChild(button);
            wrapper.appendChild(searchContainer);

            return wrapper;
        };

        /**
         * @param {WebhookLogsData | MonitoringLogsData} logsPage Webhook logs page.
         * @param {string} type
         *
         * @returns {*|HTMLElement}
         */
        const renderPagination = (logsPage, type) => {
            const rerenderPage = () => {
                let url = '', renderer;
                switch (type) {
                    case 'webhooks':
                        url = `${configuration.getWebhookLogsUrl}&pageNumber=${page}&pageSize=${limit}`;
                        renderer = renderWebhookLogsTable;
                        break;
                    case 'logs':
                        url = `${configuration.getMonitoringLogsUrl}&pageNumber=${page}&pageSize=${limit}`;
                        renderer = renderMonitoringLogsTable;
                        break;
                }

                utilities.showLoader();

                api
                    .get(url, () => {
                    })
                    .then(renderer)
                    .finally(() => {
                        utilities.hideLoader();
                    });
            }

            let paginator = generator.createElement('div', 'op-paginator');
            let label = generator.createElement(
                'div',
                '',
                translationService.translate(
                    'monitoring.pagination.numberOfItems',
                    [logsPage.beginning, logsPage.end, logsPage.numberOfItems]
                )
            );
            paginator.appendChild(label);

            const dropdownWrapper = generator.createElement('div', '');
            const dropdown = generator.createElement('div', 'op-single-select-dropdown');
            const dropdownButton = generator.createElement('button', 'op-number-of-items-button');
            const dropdownArrow = generator.createElement('span', 'op-number-of-items');
            const list = generator.createElement('ul', 'opp-dropdown-list');
            const value10 = generator.createElement('li', '', '10');
            const value25 = generator.createElement('li', '', '20');
            const value50 = generator.createElement('li', '', '50');

            value10.addEventListener('click', function () {
                limit = 10;
                page = 1;
                rerenderPage();
            });
            value25.addEventListener('click', function () {
                limit = 25;
                page = 1;
                rerenderPage();
            });
            value50.addEventListener('click', function () {
                limit = 50;
                page = 1;
                rerenderPage();
            })
            list.appendChild(value10);
            list.appendChild(value25);
            list.appendChild(value50);
            dropdownButton.appendChild(dropdownArrow);
            dropdownButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                list.classList.toggle('ops--show');
            });

            dropdown.appendChild(dropdownButton);
            dropdown.appendChild(list);
            dropdownWrapper.appendChild(dropdown);
            paginator.appendChild(dropdownWrapper);

            let navigation = generator.createElement('div', 'op-navigation');
            let previousPage = generator.createElement('div', 'op-previous-page');
            let previousPageIcon = generator.createElement('span', 'op-previous-page-icon');

            previousPage.addEventListener('click', function () {
                page = page - 1;

                rerenderPage();
            });

            if (page === 1) {
                previousPage.classList.add('op-inactive');
            }

            previousPage.appendChild(previousPageIcon);
            navigation.appendChild(previousPage);

            let nextPage = generator.createElement('div', 'op-next-page');
            let nextPageIcon = generator.createElement('span', 'op-next-page-icon');

            nextPage.addEventListener('click', function () {
                page = page + 1;

                rerenderPage();
            });

            if (!logsPage.nextPageAvailable) {
                nextPage.classList.add('op-inactive');
            }

            nextPage.appendChild(nextPageIcon);
            navigation.appendChild(nextPage);
            paginator.appendChild(navigation);

            return paginator;
        };
    }

    OnlinePaymentsFE.MonitoringController = MonitoringController;
})();