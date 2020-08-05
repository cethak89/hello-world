var app = angular.module('app', [
    'ui.router',
    'landingModule',
    'flowerDescription',
    'purchase',
    'purchaseSuccess',
    'contactUs',
    'companyCustomer',
    'companyNewYear',
    'flowerDiscount',
    'vodafoneRed',
    'studioBloom',
    'flowers',
    'PageTagsFactoryModule',
    'rescuePasswordModule',
    'angular-spinkit',
    'userAccountModule',
    'pascalprecht.translate',
    'studioBloomSuccess',
    'locations',
    'godiva',
    'companySales',
    'subscriptionFlower',
    'subscriptionReceiver',
    'subscriptionCard',
    'subscriptionSuccess'

]);

app.config(function ($stateProvider, $urlRouterProvider, $locationProvider, $translateProvider) {
    $translateProvider.useStaticFilesLoader({
        prefix: '/i18n/',
        suffix: '.json'
    });

    $translateProvider.preferredLanguage('tr');
    $translateProvider.useSanitizeValueStrategy('escapeParameters');

    $stateProvider
        .state('landing', {
            url: "/?:promo:menu:tab",
            templateUrl: "../views/main-pages/landingPage-v2.1.0.html",
            controller: "LandingCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, initLanguages) {
                    translateHelper.changeLanguage("tr");
                    return null;
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.initFlowers;
                },
                promosObj: function (promoFactory,langObj) {
                    return promoFactory.getPromos();
                }
            }
        })
        .state('landing-en', {
            url: "/en?:promo:menu:tab",
            templateUrl: "../views/main-pages/landingPage-v2.0.8.html",
            controller: "LandingCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, initLanguages) {
                    translateHelper.changeLanguage("en");

                    return null;
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.initFlowers;
                },
                promosObj: function (promoFactory,langObj) {
                    return promoFactory.getPromos();
                }
            }
        })
        .state('purchaseProcess', {
            url: "/{baseUrl:(?:satin-alma|order-flowers)}/:purchaseStep?orderId",
            templateUrl: "../views/main-pages/purchaseProcess-v2.0.2.html",
            controller: "purchaseController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "order-flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                }
            }
        })
        .state('subs1', {
            url: "/{baseUrl:(?:abonelik-1|order-uyelik-flowers)}",
            templateUrl: "../views/main-pages/purchaseSubscription-v2.0.2.html",
            controller: "subscriptionFlowerCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "order-flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                }
            }
        })
        .state('subs2', {
            url: "/{baseUrl:(?:abonelik-2|order-uyelik-flowers)}",
            templateUrl: "../views/main-pages/purchaseSubscriptionReceiver-v2.0.2.html",
            controller: "subscriptionReceiverController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "order-flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                }
            }
        })
        .state('subs3', {
            url: "/{baseUrl:(?:abonelik-3|order-uyelik-flowers)}?orderId",
            templateUrl: "../views/main-pages/purchaseSubscriptionCard-v2.0.2.html",
            controller: "subscriptionCardController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "order-flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                }
            }
        })
        .state('subsSuccess', {
            url: "/{baseUrl:(?:abonelik-basarili|abonelik-success)}?orderId",
                                        templateUrl: "../views/main-pages/succesSubs-v2.0.2.html",
            controller: "subscriptionSuccessController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "order-flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                }
            }
        })
        .state('purchaseSubscriptionLanding', {
            url: "/{baseUrl:(?:uyelik-bilgi|order-uyelik-flowers)}",
            templateUrl: "../views/main-pages/landingSubscription-v2.0.2.html",
            controller: "subscriptionFlowerCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "order-flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                }
            }
        })
        .state('how', {
            url: "/{baseUrl:(?:nasil-yapiyoruz|how)}/:howChapter",
            templateUrl: "../views/main-pages/howWeDoIt-v2.0.html",
            controller: function($state, PageTagsFactory, analyticsHelper, $rootScope){

                $rootScope.canonical = 'https://bloomandfresh.com/nasil-yapiyoruz/';

                controllerForAnalytic("Nasıl Yapıyoruzu",$state, PageTagsFactory, analyticsHelper);
            },
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "how") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        /*.state('flowers', {
            url: "/{baseUrl:(?:cicekler|flowers)}/:tagUrl",
            templateUrl: "../views/main-pages/flowers-v2.0.2.html",
            controller: "FlowersController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                tagsObj: function (tagFactory) {
                    return tagFactory.getTags();
                }
            }
        })*/
        .state('flowerDescription', {
            url: "/:flowerCategory/:flowerName-:id",
            //url: "/{baseUrl:(?:cicek-detay|flower-details)}/:flowerName-:id",
            templateUrl: "../views/main-pages/flowerDetail-v2.0.3.html",
            controller: "FlowerDetailController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "istanbul-online-flower") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                }
            }
        })
        .state('purchaseSuccess', {
            url: "/{baseUrl:(?:satis-ozet|order-details)}?orderId",
            templateUrl: "../views/main-pages/purchaseSuccess-v2.0.html",
            controller: "purchaseSuccessController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "order-details") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                }
            }
        })
        .state('help', {
            url: "/{baseUrl:(?:destek|help)}",
            templateUrl: "../views/main-pages/faq-v2.0.2.html",
            controller: function($state, PageTagsFactory, analyticsHelper, $rootScope){

                $rootScope.canonical = 'https://bloomandfresh.com/destek';

                controllerForAnalytic("Desteği",$state, PageTagsFactory, analyticsHelper);
            },
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "help") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('contactUs', {
            url: "/{baseUrl:(?:bize-ulasin|contact-us)}",
            templateUrl: "../views/main-pages/contactUs-v2.0.1.html",
            controller: "ContactUsCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "contact-us") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('companyCustomer', {
            url: "/{baseUrl:(?:kurumsal-siparisler|company-customer)}",
            templateUrl: "../views/main-pages/companyContact-v2.0.0.html",
            controller: "companyCustomerCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('companyNewYear', {
            url: "/{baseUrl:(?:kurumsal-yilbasi-hediyeleri|company-customer-new-year)}",
            templateUrl: "../views/main-pages/companyNewYear-v2.0.0.html",
            controller: "companyNewYearCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('discountFlower', {
            url: "/{baseUrl:(?:bloomandfresh-indirim-turkcell-platinum|discount-flower)}",
            templateUrl: "../views/main-pages/discountFlower-v2.0.0.html",
            controller: "flowerDiscountCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('discountTROY', {
            url: "/{baseUrl:(?:bloomandfresh-indirim-troy|bloomandfresh-indirim-troy)}",
            templateUrl: "../views/main-pages/discountTROY-v2.0.0.html",
            controller: "flowerDiscountCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('vodafoneRed', {
            url: "/{baseUrl:(?:vodafone-red-kampanyasi|vodafone-special)}",
            templateUrl: "../views/main-pages/vodafonePage-v2.0.0.html",
            controller: "vodafoneRedCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "vodafone-special") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('studioBloomPayment', {
            url: "/{baseUrl:(?:studioBloom-odeme-sayfasi|studioBloom-odeme-sayfasi)}?orderId?error",
            templateUrl: "../views/main-pages/studioBloomPayment.html",
            controller: "studioBloomController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('studioBloomPaymentSuccess', {
            url: "/{baseUrl:(?:studioBloom-satis-basarili|studioBloom-satis-basarili)}?orderId",
            templateUrl: "../views/main-pages/studioBloomSuccess.html",
            controller: "studioBloomSuccessController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('company8mart', {
            url: "/{baseUrl:(?:8-mart-kurumsal-siparisleri|company-customer)}",
            templateUrl: "../views/main-pages/temp8martInfo.html",
            controller: "companyCustomerCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('tempNewLocation', {
            url: "/{baseUrl:(?:yeni-eklenen-lokasyonlar|company-customer)}",
            templateUrl: "../views/main-pages/tempNewLocation.html",
            controller: "companyCustomerCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-customer") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('locationPage', {
            url: "/{baseUrl:(?:istanbul-cicek-siparisi|istanbul-online-delivery)}",
            templateUrl: "../views/main-pages/locations.html",
            controller: "locationsCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "istanbul-online-delivery") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('locationPageAnk', {
            url: "/{baseUrl:(?:ankara-cicek-siparisi|ankara-online-delivery)}",
            templateUrl: "../views/main-pages/locationAnk.html",
            controller: "locationsCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "istanbul-online-delivery") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('locationPageBursa', {
            url: "/{baseUrl:(?:bursa-cicek-siparisi|ankara-online-delivery)}",
            templateUrl: "../views/main-pages/locationBursa.html",
            controller: "locationsCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "istanbul-online-delivery") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('locationPageAntalya', {
            url: "/{baseUrl:(?:antalya-cicek-siparisi|ankara-online-delivery)}",
            templateUrl: "../views/main-pages/locationAntalya.html",
            controller: "locationsCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "istanbul-online-delivery") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('locationPageIzmir', {
            url: "/{baseUrl:(?:izmir-cicek-siparisi|ankara-online-delivery)}",
            templateUrl: "../views/main-pages/locationIzmir.html",
            controller: "locationsCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "istanbul-online-delivery") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('cikolatPage', {
            url: "/{baseUrl:(?:godiva-cikolata-gonder|godiva-cikolata-gonder)}",
            templateUrl: "../views/main-pages/godiva-v2.html",
            controller: "godivaCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "godiva-cikolatalar-en") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                tagsObj: function (tagFactory) {
                    return tagFactory.getTags();
                }
            }
        })
        .state('companySales', {
            url: "/{baseUrl:(?:/company-user-sales|company-user-sales)}",
            templateUrl: "../views/main-pages/companySales.html",
            controller: "companySalesController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "company-user-sales") {

                        translateHelper.changeLanguage("tr");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('contracts', {
            url: "/sozlesmeler",
            templateUrl: "../views/main-pages/contractsPage-v2.0.html",
            controller: function($state, PageTagsFactory, analyticsHelper){
                controllerForAnalytic("Sözleşmeleri",$state, PageTagsFactory, analyticsHelper);
            }
        })
        .state('aboutUs', {
            url: "/{baseUrl:(?:hakkimizda|the-team)}",
            templateUrl: "../views/main-pages/aboutUs-v2.0.4.html",
            controller: function($state, PageTagsFactory, analyticsHelper, $http, $scope, otherExceptions, $rootScope){

                $rootScope.canonical = 'https://bloomandfresh.com/hakkimizda';

                $http.get(webServer + '/aboutUs-list')
                    .success(function (data) {
                        console.log(data);
                        $scope.people = data.people;
                    })
                    .error(function () {
                        otherExceptions.sendException("deviveryLocations", "Bölgeleri Çekerken Serverdan Hata Döndü");
                    });
                console.log($scope.people);
                controllerForAnalytic("Hakkımızdayı",$state, PageTagsFactory, analyticsHelper);
            },
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "the-team") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('rescuePassword', {
            url: "/{baseUrl:(?:sifre-degistir|change-password)}?:userId&:token",
            templateUrl: "../views/main-pages/rescuePassword-v2.0.html",
            controller: "rescuePasswordCtrl",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "change-password") {

                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                }
            }
        })
        .state('flowers', {
            url: "/:tagUrl",
            templateUrl: "../views/main-pages/flowers-v2.0.2.html",
            controller: "FlowersController",
            resolve: {
                initLanguages: function (translateHelper) {
                    return translateHelper.initLanguages();
                },
                langObj: function (translateHelper, $stateParams, initLanguages) {
                    if ($stateParams.baseUrl === "flowers") {
                        translateHelper.changeLanguage("en");
                    } else
                        translateHelper.changeLanguage("tr");

                    return null;
                },
                flowersObj: function (flowerFactory, langObj) {
                    return flowerFactory.getFlowers();
                },
                districtObj: function (districtFactory) {
                    return districtFactory.getDistincts();
                },
                tagsObj: function (tagFactory) {
                    return tagFactory.getTags();
                }
            }
        });

    $urlRouterProvider.otherwise("/");
    $locationProvider.html5Mode(true).hashPrefix('!');
});

function controllerForAnalytic(pageName,$state, PageTagsFactory, analyticsHelper) {
    PageTagsFactory.changeAndSetVariables();

    analyticsHelper.sendPageView($state.current.name);
    //kissmetricsHelper.sendPageView(pageName);
}

app.controller('appController', function ($rootScope, $scope,deviceDetector) {
    $scope.isRouteLoading = false;

    $scope.checkBrowserCompatible = function(){
        var browserInfo = deviceDetector.getBrowserInfo();

        return !(browserInfo.browser == "Firefox" && browserInfo.version < 20);
    };

    $rootScope.$on('$stateChangeSuccess', function () {
        if($scope.checkBrowserCompatible()){
            $scope.isRouteLoading = false;
            scrollToTop();
        }else{
            angular.element(".mozillaError").removeClass("hidden");
        }
    });

    $rootScope.$on('$stateChangeStart', function () {
        $scope.isRouteLoading = true;
    });
});