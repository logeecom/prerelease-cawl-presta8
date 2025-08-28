if (!window.OnlinePaymentsFE) {
    window.OnlinePaymentsFE = {};
}

(function () {
    /**
     * The ResponseService constructor.
     *
     * @constructor
     */
    function ResponseService() {
        /**
         * Handles an error response from the submit action.
         *
         * @param {{error?: string, errorCode?: string, status?: number}} response
         * @returns {Promise<void>}
         */
        this.errorHandler = (response) => {
            if (response.status !== 401) {
                const { utilities, templateService, elementGenerator } = OnlinePaymentsFE;
                let container = document.querySelector('.opp-flash-message-wrapper');
                if (!container) {
                    container = elementGenerator.createElement('div', 'opp-flash-message-wrapper');
                    templateService.getMainPage().prepend(container);
                }

                templateService.clearComponent(container);

                if (response.error) {
                    container.prepend(utilities.createToasterMessage(response.error, 'error'));
                } else if (response.errorCode) {
                    container.prepend(utilities.createToasterMessage('general.errors.' + response.errorCode, 'error'));
                } else {
                    container.prepend(utilities.createToasterMessage('general.errors.unknown', 'error'));
                }
            }

            return Promise.reject(response);
        };

        /**
         * Handles 401 response.
         *
         * @param {{error?: string, errorCode?: string}} response
         * @returns {Promise<void>}
         */
        this.unauthorizedHandler = (response) => {
            OnlinePaymentsFE.utilities.create401FlashMessage(`general.errors.${response.errorCode}`);
            OnlinePaymentsFE.state.goToState('connection');

            return Promise.reject({ ...response, status: 401 });
        };
    }

    OnlinePaymentsFE.responseService = new ResponseService();
})();
