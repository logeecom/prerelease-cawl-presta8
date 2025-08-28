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
     * @typedef Translation
     *
     * @property {string} language
     * @property {string} translation
     */

    /**
     * @typedef Language
     *
     * @property {string} code
     * @property {string} logo
     */

    /**
     * @typedef TranslatableLabelModel
     *
     * @property {Language[]} languages
     * @property {Translation[]} translations
     * @property {string} name
     * @property onChange
     */

    const TranslatableLabel = (configuration) => {
        const { elementGenerator: generator } = OnlinePaymentsFE;
        let values = [];

        /**
         * @param {string} lang
         */
        const findIndex = (lang) => {
            return values.findIndex(value => value.locale === lang);
        }

        values = OnlinePaymentsFE.utilities.cloneObject(configuration.translations);
        let currentLang = configuration.translations[0] !== undefined ?
            configuration.translations[0].locale : 'en';
        const wrapper = generator.createElement('div', 'op-translatable-label');
        const input = generator.createElement('input', 'op-lang-label-input');
        input.type = 'text';
        input.name = configuration.name;
        input.value = values[0] !== undefined ? values[0].value : '';
        input.addEventListener('change', (e) => {
            e.preventDefault();
            let index = findIndex(currentLang);
            if (values[index] === undefined) {
                values.push({'locale': currentLang, 'value': input.value});
            } else {
                values[index]['value'] = input.value;
            }

            configuration.onChange();
        });

        const dropdown = generator.createElement('div', 'op-lang-dropdown');
        const flagImg = generator.createElement('img');
        flagImg.src =  configuration.languages[currentLang].logo;
        dropdown.appendChild(flagImg);

        const options = generator.createElement('div', 'op-lang-options');

        for (const key in configuration.languages) {
            const opt = generator.createElement('div');
            const flag = generator.createElement('img');
            flag.src = configuration.languages[key].logo;
            flag.style.marginRight = '6px';
            opt.appendChild(flag);
            opt.onclick = (e) => {
                preventDefaults(e);
                let index = findIndex(currentLang);

                if (values[index] === undefined) {
                    values.push({'locale': currentLang, 'value': input.value});
                } else {
                    values[index]['value'] = input.value;
                }

                currentLang = configuration.languages[key].code;
                index = findIndex(currentLang);
                input.value = values[index] !== undefined ? values[index].value : '';
                flagImg.src = configuration.languages[key].logo;
                options.style.display = 'none';
            }
            options.appendChild(opt);
        }

        dropdown.appendChild(options);
        dropdown.onclick = (e) => {
            preventDefaults(e);
            options.style.display = options.style.display === 'block' ? 'none' : 'block';
        }

        wrapper.appendChild(input);
        wrapper.appendChild(dropdown);

        return {
            element: wrapper,
            getValues: () => values
        };
    }

    OnlinePaymentsFE.components.TranslatableLabel = {
        /**
         * @param {TranslatableLabelModel} config
         * @returns {HTMLElement}
         */
        create: (config) => TranslatableLabel(config),
        getConfiguredValues: () => {
            return values;
        }
    };
})();