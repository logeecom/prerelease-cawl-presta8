const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const dotenv = require('dotenv');
const webpack = require('webpack');
const fs = require('fs');

module.exports = (env = {}, argv) => {
    const isProduction = argv.mode === 'production';
    const jsEntryPath = env.inputPath || path.resolve(__dirname, '../BusinessLogic/AdminConfig/Resources/src/index.js');

    const buildConfigForBrand = (brandConfig) => {
        const brand = brandConfig.code;
        const brandPath = path.resolve(__dirname, brand);
        const envFile = env && env.environment ? `.env.${env.environment}` : '.env';
        const envVars = dotenv.config({ path: envFile }).parsed || {};
        const OUTPUT_PATH =
            process.env.OUTPUT_PATH ||
            path.resolve(__dirname, brand, 'dist');
        const outputPath = env.outputPath !== undefined ? path.resolve(__dirname, env.outputPath, brand, 'dist') : OUTPUT_PATH;

        if (!fs.existsSync(brandPath)) {
            return null;
        }

        fs.writeFileSync(`${brandPath}/brand.json`, JSON.stringify(brandConfig, null, 2), 'utf-8');

        const envKeys = Object.keys(envVars).reduce((prev, next) => {
            prev[`process.env.${next}`] = JSON.stringify(envVars[next]);
            return prev;
        }, {});
        const baseLangPath = path.resolve(__dirname, '..', 'BusinessLogic', 'AdminConfig', 'Resources', 'src', 'lang');
        const brandLangPath = path.resolve(brandPath, 'lang');
        const outputLangPath = path.resolve(outputPath, 'lang');

        // Create merged language JSONs
        if (fs.existsSync(baseLangPath) && fs.existsSync(brandLangPath)) {
            const langFiles = fs.readdirSync(baseLangPath).filter(file => file.endsWith('.json'));

            langFiles.forEach(file => {
                const baseFile = path.resolve(baseLangPath, file);
                const brandFile = path.resolve(brandLangPath, file);
                const outputFile = path.resolve(outputLangPath, file);

                const baseContent = JSON.parse(fs.readFileSync(baseFile, 'utf-8'));
                const brandContent = fs.existsSync(brandFile)
                    ? JSON.parse(fs.readFileSync(brandFile, 'utf-8'))
                    : {};

                const merged = { ...baseContent, ...brandContent };

                fs.mkdirSync(outputLangPath, { recursive: true });
                fs.writeFileSync(outputFile, JSON.stringify(merged, null, 2), 'utf-8');
            });
        }

        return {
            name: brand, // Needed when returning multiple configs
            mode: isProduction ? 'production' : 'development',
            entry: {
                index: [
                    path.resolve(jsEntryPath),
                    path.resolve(brandPath, 'design/index.scss')
                ]
            },
            output: {
                path: outputPath,
                filename: 'js/[name].js'
            },
            module: {
                rules: [
                    {
                        test: /\.js$/,
                        loader: 'string-replace-loader',
                        options: {
                            search: /OnlinePaymentsFE/g,
                            replace: brand
                        }
                    },
                    {
                        test: /\.html$/,
                        use: [
                            {
                                loader: require.resolve('html-webpack-plugin/lib/loader'),
                                options: {
                                    force: true
                                }
                            },
                            {
                                loader: 'string-replace-loader',
                                options: {
                                    search: /OnlinePaymentsFE/g,
                                    replace: brand
                                }
                            }
                        ]
                    },
                    {
                        test: /\.scss$/,
                        use: [
                            MiniCssExtractPlugin.loader,
                            'css-loader',
                            {
                                loader: 'sass-loader',
                                options: {
                                    sourceMap: !isProduction
                                }
                            }
                        ]
                    },
                    {
                        test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
                        type: 'asset/resource',
                        generator: {
                            filename: 'fonts/[name][ext]'
                        }
                    }
                ]
            },
            plugins: [
                new MiniCssExtractPlugin({
                    filename: 'css/[name].css'
                }),
                new HtmlWebpackPlugin({
                    template: path.resolve(__dirname, 'index.html'),
                    filename: 'index.html',
                    inject: 'body',
                    templateParameters: {
                        ...envKeys,
                        brand: brandConfig
                    }
                }),
                new CopyWebpackPlugin({
                    patterns: [
                        {
                            from: path.resolve(brandPath, 'design/assets/images'),
                            to: 'images',
                            noErrorOnMissing: true
                        },
                        {
                            from: path.resolve(
                                __dirname,
                                '../BusinessLogic/AdminConfig/Resources/src/design/assets/images'
                            ),
                            to: 'images',
                            noErrorOnMissing: true
                        }
                    ]
                }),
                new webpack.DefinePlugin(envKeys)
            ],
            resolve: {
                extensions: ['.scss', '.css', '.html', '.js']
            },
            devServer: {
                port: 9001,
                compress: true,
                hot: true,
                historyApiFallback: true
            }
        };
    };

    if (env.brand) {
        const brandsJson = JSON.parse(fs.readFileSync('./brands.json', 'utf-8'));
        const brandConfig = {
            code: env.brand,
            ...(brandsJson.brands[env.brand] || {})
        };
        return buildConfigForBrand(brandConfig);
    } else {
        const brandsJson = JSON.parse(fs.readFileSync('./brands.json', 'utf-8'));
        const brands = Object.entries(brandsJson.brands).map(([code, config]) => ({
            code,
            ...config
        }));
        return brands.map(buildConfigForBrand).filter(Boolean);
    }
};
