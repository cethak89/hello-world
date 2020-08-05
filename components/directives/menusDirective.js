/**
 * Created by furkan on 11.03.2015.
 */

'use strict'; //http://188.166.86.116:3000

var menu = angular.module("menuModule", [
    'userAccountModule',
    'ui.mask',
    'ui.bootstrap',
    'ui.validate',
    'facebook',
    'ngLoader',
    'ui.bootstrap.tpls',
    'pascalprecht.translate'
]);
menu.config(function (FacebookProvider) {
    //FacebookProvider.init('945995238783899'); //for test
    FacebookProvider.init('769158816501537');   //for production
});
menu.directive('navbarMenu', function () {
    return {
        restrict : 'E',
        controller : function ($scope,translateHelper,$state,$rootScope, userAccount,$timeout,flowerFactory) {
            $scope.isUserLogin = function () {
                return $scope.loggedUser !== undefined;
            };
            $scope.upMenu = flowerFactory.controllerForUpMenu();
            userAccount.getUser(function (user) {
                if (user === undefined)
                    $scope.loggedUser = undefined;
                else
                    $scope.loggedUser = user;
            },false);

            $timeout(function () {
                if( $scope.loggedUser ){
                    if($scope.loggedUser.company_info_id)
                        flowerFactory.checkCompanyFlowers($scope.loggedUser.company_info_id,function(data){
                                $scope.companyStatus = data;
                            }
                        );
                }
            }, 1000);

            $scope.checkLandingUrl = function(lang){
                return translateHelper.getCurrentLang() === lang;
            };

            $scope.goLandingFromCompany = function(){
                $rootScope.companyOrElse = true;
                $state.go('landing');
            };

            $scope.langChanged = function(){
                $timeout(function(){
                    location.reload();
                },100);
            };

            $scope.checkCompanyUser = function(){
                if (userAccount.checkUserLoggedin()){
                    if($scope.loggedUser){
                        return $scope.loggedUser.company_info_id;
                    }
                    else
                        return false;
                }
                else
                    return false;
            };
        },
        templateUrl : "../../views/menu-views/menu-v2.0.1.html"
    }
});
menu.directive('leftHiddenMenu', function () {
    return {
        restrict : 'E',
        controller : function ($scope,translateHelper, $rootScope) {
            $scope.menuOpen = 0;
            $scope.isMenuOpen = function () {
                return $scope.menuOpen;
            };

            $scope.ifUserCompany = function () {
                if($scope.loggedUser){
                    if(!$scope.loggedUser.companyAdmin){
                        return false;
                    }
                    if($scope.loggedUser.companyAdmin == 1){
                        return true;
                    }
                    else
                        return false;
                }
                else{
                    return false;
                }
            };
            $scope.setMenuVisibility = function (boolean) {
                $scope.menuOpen = boolean;

                if($scope.menuOpen){
                    $rootScope.megaDropDown = false;

                    //kissmetricsHelper.clickEvent('.mainPage',"Sol menüden ana sayfaya tıkladı");
                    //kissmetricsHelper.clickEvent('.flowers',"Sol menüden çiçeklere tıkladı");
                    //kissmetricsHelper.clickEvent('.aboutUs',"Sol menüden hakkımızda'ya tıkladı");
                    //kissmetricsHelper.clickEvent('.contactUs',"Sol menüden bize ulaş'a tıkladı");
                    //kissmetricsHelper.clickEvent('.how',"Sol menüden nasıl'a tıkladı");
                    //kissmetricsHelper.clickEvent('.help',"Sol menüden destek'e tıkladı");
                    //kissmetricsHelper.outboundClickEvent('.blog',"Sol menüden blog'u tıkladı");
                }
            };

            $scope.checkLandingUrl = function(lang){
                return translateHelper.getCurrentLang() === lang;
            };
        },
        templateUrl : "../../views/menu-views/leftHiddenMenu-v2.0.html"
    }
});
menu.directive('megaDropDown', function () {
    return {
        restrict : 'E',
        controller : function ($scope,translateHelper, $http, $rootScope, analyticsHelper, $cookies, districtFactory) {
            $rootScope.megaDropDown = false;
            $scope.isDropDownOpen = function () {
                return $rootScope.megaDropDown;
            };

            $rootScope.city = {};
            $scope.selectedCity = {};
            var cityFromPlugin = '';

            $http.get(webServer + '/get-ups-status' )
                .success(function (data) {
                    if( data.data == 1 ){

                        $http.get(webServer + '/getUPSCities' )
                            .success(function (data) {
                                $scope.cities = data;
                                //console.log(data);

                                var tempSelectedCity = $cookies.getObject('selectCity');

                                if( tempSelectedCity.value != 'ist' && tempSelectedCity.value != 'ank'  && tempSelectedCity.value != 'ist-2' ){

                                    if( !tempSelectedCity.delivery_days ) {

                                        $scope.cities.forEach(function (city) {

                                            if (city.value == $rootScope.mainCitySelected.value) {
                                                $rootScope.mainCitySelected = city;
                                                $scope.selectedCity = $rootScope.mainCitySelected;
                                                //console.log(city);
                                                $cookies.putObject('selectCity', city);
                                            }

                                        });
                                    }
                                }

                            });

                    }
                    else{
                        $scope.cities = [
                            {
                                "value": "ist",
                                "name": "İstanbul"
                            },
                            {
                                "value": "ank",
                                "name": "Ankara"
                            }
                        ];
                    }
                })
                .error(function () {
                    otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                });



            var tempSelectedCity = $cookies.getObject('selectCity');

            var veryTempCity = 'ist';
            /*if( tempSelectedCity ){
             if( tempSelectedCity.value == 'ist' ){
             tempSelectedCity = {
             'value': 'ist',
             'name': 'İstanbul'
             };
             }
             else{
             tempSelectedCity = {
             'value': 'ank',
             'name': 'Ankara'
             };
             }
             veryTempCity = tempSelectedCity.value;
             }*/

            if ( tempSelectedCity == null) {

                $cookies.putObject('selectCity', {
                    'value': 'ist',
                    'name': 'İstanbul-Avrupa'
                });
                tempSelectedCity = {
                    'value': 'ist',
                    'name': 'İstanbul-Avrupa'
                };
            }

            $rootScope.mainCitySelected = tempSelectedCity;
            $scope.selectedCity = $rootScope.mainCitySelected;

            $(document).on("mousemove", function(event){

                if( $rootScope.megaDropDown ){
                    if($scope.menuOpen){
                        $('#megaOverlay').removeClass('open');
                        $rootScope.megaDropDown = false
                    }
                    if( event.pageY > 530 ){
                        $('#megaOverlay').removeClass('open');
                        $rootScope.megaDropDown = false
                    }
                }
            });

            $scope.column1 = [];
            $scope.column2 = [];
            $scope.column3 = [];
            $scope.menuBanner = {};

            $http.get(webServer + '/drop-down-menu' )
                .success(function (data) {
                    $scope.column1 = data.column1;
                    $scope.column2 = data.column2;
                    $scope.column3 = data.column3;
                    $scope.menuBanner = data.menuBanner;
                })
                .error(function () {
                    otherExceptions.sendException("flowerFactory", "Çiçekleri Çekerken Serverdan Hata Döndü");
                });

            $scope.setDropDown = function () {
                $rootScope.megaDropDown = !$rootScope.megaDropDown;

                if($rootScope.megaDropDown){

                    if( $('#menuBannerImage').attr('name') ){
                        $('#menuBannerImage').attr("src", $('#menuBannerImage').attr('name'));
                        $('#menuBannerImage').removeAttr('name');
                    }

                    analyticsHelper.clickRibbonMenu();
                    $('#megaOverlay').addClass('open');
                }
                else{
                    $('#megaOverlay').removeClass('open');
                }

            };

            $scope.setCity = function (city) {
                $cookies.putObject('selectCity', city);
                $rootScope.mainCitySelected = city;
                tempSelectedCity = $rootScope.mainCitySelected;
                $scope.selectedCity = $rootScope.mainCitySelected;

                var tempNameCity = "1";
                if( $rootScope.mainCitySelected.value == 'ank' ){
                    tempNameCity = 'ANKARA';
                }
                else if( $rootScope.mainCitySelected.value == 'ist' ){
                    tempNameCity = 'İSTANBUL-Avrupa';
                }
                else if( $rootScope.mainCitySelected.value == 'ist-2' ){
                    tempNameCity = 'İSTANBUL-Asya';
                }
                else{
                    tempNameCity = $rootScope.mainCitySelected.value.toUpperCase();
                }

                districtFactory.getDistinctsCallBack(function(data){
                    $scope.districts = data;
                    $scope.districts = $scope.districts.filter(function (el) {

                        return ( ( el.city.toUpperCase() == tempNameCity && ( el.city_id == 2 || el.city_id == 3 ) ) || ( el.city_id == 1 && $rootScope.mainCitySelected.value == 'ist'  ) || ( el.city_id == 341 && $rootScope.mainCitySelected.value == 'ist-2'  ) );
                    });
                });

                if($scope.flower){
                    $scope.flower.sendingDistrict = null;
                }

                if( $('#tiggerButton') ){
                    $('#tiggerButton').click();
                }

                //$cookies.put('selectedCity', $rootScope.chosenCampaign);
            };



            $scope.checkLandingUrl = function(lang){
                return translateHelper.getCurrentLang() === lang;
            };
        },
        templateUrl : "../../views/menu-views/megaDropDown-v2.0.html"
    }
});
menu.directive('rightMenus', [
    'userAccount','$rootScope', function () {
        return {
            restrict : 'E',
            controller : function ($scope, $state, $timeout, $rootScope, translateHelper,scrollBarHelper, analyticsHelper, userAccount) {
                $scope.signSection = "";
                $scope.processSuccess = false;
                $scope.working = false;

                $scope.isSignOpen = function (sectionName) {

                    if( $scope.signSection ){
                        $('html').addClass('indexMobileOverFlow');
                        $('#mainBody').addClass('indexMobileOverFlow');
                    }
                    else{
                        $('html').removeClass('indexMobileOverFlow');
                        $('#mainBody').removeClass('indexMobileOverFlow');
                    }

                    return $scope.signSection === sectionName;
                };

                $scope.setSignSection = function (sectionName) {
                    scrollToTop();
                    $rootScope.megaDropDown = false;

                    switch (sectionName) {
                        case 'profil' :
                        {
                            if ($scope.loggedUser !== undefined && userAccount.checkUserExit()) {
                                scrollBarHelper.unloadScrollBars();
                                //analytics for profile page is done under setTab function
                                if($scope.tab === undefined)
                                    $scope.setTab('myBloom');
                                else
                                    $scope.setTab($scope.tab);
                                $scope.signSection = sectionName;
                                $scope.updateUserInfo();
                            } else {
                                $scope.errorMessage = "giriş yaparken bir hata oluştu";
                                $scope.loggedUser = undefined;
                                $scope.setSignSection('giris');
                            }
                            $scope.processSuccess = false;
                            break;
                        }
                        case '':
                        {
                            scrollBarHelper.reloadScrollBars();
                            resetForms();
                            $scope.signSection = sectionName;
                            $rootScope.$broadcast("MENU_CLOSED");
                            break;
                        }
                        case 'giris':
                        {
                            userAccount.getUser(function(user){
                                if(user === undefined){
                                    //kissmetricsHelper.sendPageView('Giris Yap');
                                    openMenu(sectionName);
                                }else{
                                    $scope.loggedUser = user;
                                    $scope.setSignSection('profil');
                                }
                            },false);
                            break;
                        }
                        case 'kayit':
                        {
                            userAccount.getUser(function(user){
                                if(user === undefined){
                                    //kissmetricsHelper.sendPageView('Kayıt Ol');
                                    openMenu(sectionName);
                                }else{
                                    $scope.loggedUser = user;
                                    $scope.setSignSection('profil');
                                }
                            },false);

                            break;
                        }
                        case 'hatirlatmaEkle':
                        {
                            //kissmetricsHelper.sendPageView('Hatırlatma Ekleyi');
                            openMenu(sectionName);
                            break;
                        }
                        case 'passwordRetrieval':
                        {
                            //kissmetricsHelper.sendPageView('Şifre kurtarma');
                            openMenu(sectionName);
                            break;
                        }
                        case 'beforePurchaseSection':
                        {
                            //kissmetricsHelper.sendPageView("Satış Öncesi Üye ol'u");
                            openMenu(sectionName);
                            break;
                        }
                        case 'contactAdd':
                        {
                            //kissmetricsHelper.sendPageView("Kişi Ekleyi");
                            openMenu(sectionName);
                            break;
                        }
                        default :
                        {
                            //kissmetricsHelper.sendPageView(sectionName);
                            openMenu(sectionName);
                        }
                    }
                };
                function openMenu(sectionName){
                    $rootScope.megaDropDown = false;
                    $rootScope.$broadcast("MENU_OPENED");
                    analyticsHelper.sendPageView('/'+sectionName); //other right menu forms' analytics are sent from here
                    scrollBarHelper.unloadScrollBars();
                    $scope.signSection = sectionName;
                }

                $scope.resetForm = function (form) {
                    form.$setPristine();
                    form.$setUntouched();
                    $scope.isChecked = false;
                };
                $scope.isUserLogin = function () {
                    return $scope.loggedUser !== undefined;
                };
                $scope.openMenuFromQueryParams = function(menuName,tabName){

                    switch (menuName){
                        case 'profil':{
                            if(userAccount.checkUserLoggedin()){
                                if(tabName !== undefined && tabName !== null){
                                    $scope.tab = tabName;
                                }else{
                                    $scope.tab ='myBloom';
                                }

                                $scope.setSignSection(menuName);
                            }else{
                                $scope.setSignSection('giris');
                            }
                        }
                        default :{
                            $scope.setSignSection(menuName);
                        }
                    }
                };
                $scope.goToPurchase = function () {
                    translateHelper.getText('CHECKOUT_URL', function(checkout_url) {
                        $state.go('purchaseProcess',{baseUrl:checkout_url});
                        $scope.toPurchase = false;
                        scrollBarHelper.reloadScrollBars();
                    });
                };
                $scope.isSelected = function (tab) {
                    return $scope.tab === tab;
                };
                $scope.setTab = function (newTab) {

                    switch(newTab){
                        case 'siparislerin':
                            $scope.tab = newTab;
                            break;
                            //kissmetricsHelper.sendPageView("Siparişlerin'i"); break;
                        case 'hatırlatma':
                            $scope.tab = newTab;
                            break;
                            //kissmetricsHelper.sendPageView("Hatırlatmaların'ı"); break;
                        case 'kisilerin':
                            $scope.tab = newTab;
                            break;
                            //kissmetricsHelper.sendPageView("Kişilerin'i"); break;
                        case 'myBloom':
                            $scope.tab = newTab;
                            break;
                            //kissmetricsHelper.sendPageView("MyBloom'u"); break;
                        case 'hesapBilgilerin':
                            $scope.tab = newTab;
                            break;
                            //kissmetricsHelper.sendPageView("Hesap Bilgilerin'i"); break;
                        default :{
                            $scope.tab = 'myBloom';
                            break;
                            //kissmetricsHelper.sendPageView("MyBloom'u");
                        }
                    }

                    analyticsHelper.sendPageView('/profil-' + $scope.tab);
                };
                $scope.progressSucceed = function () {
                    $scope.processSuccess = true;
                    $scope.isChecked = false;
                    $timeout(function () {
                        $scope.setSignSection('profil');
                    }, 1000);
                };
                $scope.signOut = function () {
                    userAccount.logOut();
                    $scope.loggedUser = undefined;
                    $scope.setSignSection('');
                    $scope.processSuccess = false;
                    //_kmq.push(['clearIdentity']);
                    switch($state.current.name){
                        case 'purchaseProcess':{
                            $rootScope.$broadcast('USER_SIGN_OUT');
                            var lang = translateHelper.getCurrentLang();
                            if(lang !== 'tr')
                                $state.go('landing' + "-" +lang);
                            break;
                        }
                        default:{
                            var lang = translateHelper.getCurrentLang();
                            if(lang !== 'tr')
                                $state.go('landing' + "-" +lang);
                            location.reload();
                        }
                    }
                };

                $scope.$on('USER_LOGIN', function (e, data) {
                    $scope.toPurchase = true;
                    $scope.setSignSection(data);
                });
                var controller = this;
                $rootScope.$on('USER_GOING_SALE', function (e, data) {
                    $scope.processSuccess = true;
                    controller.initUser();
                    if($rootScope.speciality == 1){
                        //console.log($scope.$$childHead);
                        if($scope.$$childHead){
                            $scope.setSignSection('');
                            $scope.send();
                        }
                    }
                    else{
                        $timeout(function () {
                            if ($scope.toPurchase) {
                                $scope.goToPurchase();
                            }
                            else {
                                if($state.current.name === 'purchaseProcess')
                                    $rootScope.$broadcast('USER_SIGN_IN');
                                else
                                    $scope.setSignSection("profil");
                            }
                            $scope.processSuccess = false;
                        }, 1000);
                    }
                });
                $rootScope.$on('$stateChangeSuccess', function (event) {
                    event.preventDefault();
                    scrollBarHelper.reloadScrollBars();
                });
                $rootScope.$on('UPDATE_USER', function () {
                    userAccount.getUser(function (user) {
                        $scope.loggedUser = user;
                    });
                });

                $rootScope.$watch('working.isLogInProcess',function(newValue, oldValue){
                    if(newValue !== undefined)
                        $scope.working = newValue;
                });

                $scope.updateUserInfo = function(){
                    if($scope.loggedUser.purchases === undefined)   // when there are no user data fetch from server make scope is working
                        $scope.working = true;

                    userAccount.updateUserData(function(){
                        $scope.purchases = $scope.loggedUser.purchases.getPurchases();
                        $scope.contacts = $scope.loggedUser.contacts.getContacts();
                        $scope.userBillingInfo = $scope.loggedUser.billingInfo.getBillingInfo();
                        $scope.campaigns = $scope.loggedUser.campaigns.getCampaigns();
                        $scope.reminders = $scope.loggedUser.reminders.getReminders();
                        $scope.working = false;
                    });
                };
                function resetForms(){
                    //console.log($scope.signInForm);
                }
                this.initUser = function () {
                    userAccount.getUser( function(user){
                        if (user === undefined)
                            $scope.loggedUser = undefined;
                        else {
                            $scope.loggedUser = user;

                            if($scope.loggedUser.purchases !== undefined){
                                $scope.purchases = $scope.loggedUser.purchases.getPurchases();
                                $scope.contacts = $scope.loggedUser.contacts.getContacts();
                                $scope.userBillingInfo = $scope.loggedUser.billingInfo.getBillingInfo();
                                $scope.campaigns = $scope.loggedUser.campaigns.getCampaigns();
                                $scope.reminders = $scope.loggedUser.reminders.getReminders();
                            }
                        }
                    },false);
                };

                $rootScope.$on("BillingChanged",function(){
                    $scope.updateUserInfo();
                });
            },
            templateUrl : "../../views/menu-views/rightMenus-v2.0.3.html",
            link : function (scope, el, attr, controller) {
                controller.initUser();
            }
        }
    }
]);
menu.controller('SignUpCtrl', function ($scope,$rootScope, $timeout,$modal,facebookhelper,$document,adwordsHelper, userAccount,otherExceptions,analyticsHelper,errorMessages) {
    var newsLetterSubscription = true;
    var isContractAccepted = false;
    $scope.errorMessage = "";
    $scope.newUser = {};
    $scope.isChecked = false;
    $scope.working = false;
    $scope.isRegistered = false;

    $rootScope.$watch('working.isLogInProcess',function(newValue, oldValue){
        if(newValue !== undefined)
            $scope.working = newValue;
    });

    $rootScope.$watch('facebookErrorMessage',function(newValue, oldValue){
        if(newValue !== undefined)
            $scope.errorMessage = newValue;
    });

    $document.ready(function () {
        $('.informMe input').iCheck({
            checkboxClass : 'icheckbox_square-red',
            radioClass : 'iradio_flat-red'
        });
        $('input.newsletterCheckbox')
            .on('ifChecked', function (event) {
                newsLetterSubscription = true;
            })
            .on('ifUnchecked', function (event) {
                newsLetterSubscription = false;
            });
        $('input.contractCheckbox')
            .on('ifChecked', function (event) {
                isContractAccepted = true;
            })
            .on('ifUnchecked', function (event) {
                isContractAccepted = false;
            });
    });
    $scope.signUp = function () {
        if (isContractAccepted) {
            $scope.errorMessage = "";
            if ($(signUpForm.userMail).hasClass('ng-valid') && $(signUpForm.userName).hasClass('ng-valid') && $(signUpForm.userPassword).hasClass('ng-valid')) {
                $scope.working = true;
                $scope.isRegistered = true;

                userAccount.signUp($scope.newUser.email, $scope.newUser.name, $scope.newUser.password, newsLetterSubscription, function (result, errorCode) {
                    if (result) {
                        facebookhelper.trackEvent(facebookhelper.facebookAdTypes.REGISTRATION);

                        //kissmetricsHelper.registered('E-posta', $scope.newUser.email);
                        analyticsHelper.sendEvent('signUp','E-posta');

                        $scope.processSuccess = true;
                        $scope.isChecked = false;
                        $scope.working = false;
                        $rootScope.$emit('USER_GOING_SALE');
                        userAccount.afftrckRegister(result.id);

                        $timeout(function () {
                            resetForm();
                            $scope.isRegistered = false;
                        }, 800);

                        $timeout(function () {
                            adwordsHelper.signUpTrack();
                        }, 2000);
                    } else {
                        errorMessages.getErrorMessage(errorCode, function(errorMessage){
                            $scope.processSuccess = false;
                            $scope.isChecked = true;
                            $scope.working = false;
                            $scope.errorMessage = errorMessage;
                        });
                    }
                });
            } else {
                errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                    $scope.working = false;
                    $scope.isChecked = true;

                    if( !($(signUpForm.userMail).hasClass('ng-valid')) )
                        otherExceptions.sendException("signUp",  "Eksik Bilgi- mail adresi");
                    else if( !($(signUpForm.userName).hasClass('ng-valid')) )
                        otherExceptions.sendException("signUp",  "Eksik Bilgi- kullanıcı ismi");
                    else if( !($(signUpForm.userPassword).hasClass('ng-valid')) )
                        otherExceptions.sendException("signUp",  "Eksik Bilgi- kullanıcı şifresi");
                    else
                        otherExceptions.sendException("signUp",  "Eksik Bilgi");

                    $scope.errorMessage = errorMessage;
                });

            }
        } else {
            errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                otherExceptions.sendException("signUp",  $scope.errorMessage);
                $scope.errorMessage = errorMessage;
            });

        }
    };
    $scope.changeSection = function (section) {
        $scope.setSignSection(section);
        resetForm();
    };
    $scope.openModel = function (contactType) {
        switch (contactType){
            case 'membershipContract':{
                $modal.open({
                    templateUrl: 'views/contracts/membershipContract.html',
                    size: 'lg'
                });
                break;
            }
        }
    };
    function resetForm() {
        $scope.resetForm($scope.signUpForm);
        $scope.newUser = {};
        $scope.processSuccess = false;
        $('input.contractCheckbox').iCheck('uncheck');
    }
});
menu.controller('SignInCtrl', function ($scope,$rootScope, $timeout, userAccount,otherExceptions,analyticsHelper,errorMessages) {
    $scope.errorMessage = "";
    $scope.signInUser = {};
    $scope.isChecked = false;
    $scope.working = false;

    $rootScope.$watch('working.isLogInProcess',function(newValue, oldValue){
        if(newValue !== undefined)
            $scope.working = newValue;
    });

    $rootScope.$watch('facebookErrorMessage',function(newValue, oldValue){
        if(newValue !== undefined)
            $scope.errorMessage = newValue;
    });

    $scope.signIn = function () {

        $timeout(function () {
            if ($(signInForm.signInUserMail).hasClass('ng-valid') && $(signInForm.signInUserPassword).hasClass('ng-valid')) {
                $scope.signInForm.signInUserPassword.$setTouched();
                $scope.working = true;
                userAccount.signIn($scope.signInUser.email, $scope.signInUser.password, function (result, errorCode) {
                    if (result) {
                        $scope.processSuccess = true;
                        $scope.isChecked = false;
                        $scope.errorMessage = "";
                        $scope.working = false;
                        $rootScope.$emit('USER_GOING_SALE');
                        analyticsHelper.sendEvent('signIn','E-Posta');
                        //kissmetricsHelper.loggedIn('E-posta', $scope.signInUser.email);

                        $timeout(function () {
                            resetForm();
                        }, 800);
                    } else {
                        errorMessages.getErrorMessage(errorCode, function(errorMessage){
                            $scope.processSuccess = false;
                            $scope.isChecked = true;
                            $scope.working = false;
                            $scope.errorMessage = errorMessage;
                        });
                    }
                });
            }
            else {
                errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                    $scope.working = false;
                    $scope.isChecked = true;

                    if( !($(signInForm.signInUserMail).hasClass('ng-valid')) )
                        otherExceptions.sendException("signIn",  "Eksik Bilgi- mail adresi");
                    else if( !($(signInForm.signInUserPassword).hasClass('ng-valid')) )
                        otherExceptions.sendException("signIn",  "Eksik Bilgi- kullanıcı şifresi");
                    else
                        otherExceptions.sendException("signIn",  "Eksik Bilgi");

                    $scope.errorMessage = errorMessage;
                });
            }
        }, 100);

        /*if ($(signInForm.signInUserMail).hasClass('ng-valid') && $(signInForm.signInUserPassword).hasClass('ng-valid')) {
            $scope.signInForm.signInUserPassword.$setTouched();
            $scope.working = true;
            userAccount.signIn($scope.signInUser.email, $scope.signInUser.password, function (result, errorCode) {
                if (result) {
                    $scope.processSuccess = true;
                    $scope.isChecked = false;
                    $scope.errorMessage = "";
                    $scope.working = false;
                    $rootScope.$emit('USER_GOING_SALE');
                    analyticsHelper.sendEvent('signIn','E-Posta');
                    //kissmetricsHelper.loggedIn('E-posta', $scope.signInUser.email);

                    $timeout(function () {
                        resetForm();
                    }, 800);
                } else {
                    errorMessages.getErrorMessage(errorCode, function(errorMessage){
                        $scope.processSuccess = false;
                        $scope.isChecked = true;
                        $scope.working = false;
                        $scope.errorMessage = errorMessage;
                    });
                }
            });
        } else {
            errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                $scope.working = false;
                $scope.isChecked = true;

                if( !($(signInForm.signInUserMail).hasClass('ng-valid')) )
                    otherExceptions.sendException("signIn",  "Eksik Bilgi- mail adresi");
                else if( !($(signInForm.signInUserPassword).hasClass('ng-valid')) )
                    otherExceptions.sendException("signIn",  "Eksik Bilgi- kullanıcı şifresi");
                else
                    otherExceptions.sendException("signIn",  "Eksik Bilgi");

                $scope.errorMessage = errorMessage;
            });
        }*/
    };
    $scope.changeSection = function (section) {
        $scope.setSignSection(section);
        resetForm();
    };
    function resetForm() {
        $scope.resetForm($scope.signInForm);
        $scope.signInUser = {};
        $scope.processSuccess = false;
    }
});
menu.controller('AddContactCtrl', function ($scope, $rootScope, $timeout, districtFactory, otherExceptions, textareaHelper, errorMessages) {
    $scope.dropdowns = [];
    $scope.contact = {};
    $scope.errorMessage = "";
    $scope.isChecked = false;
    $scope.isEditContact = false;
    $scope.working = false;
    textareaHelper.checkMaxLength();

    var response = districtFactory.getDistincts();
    if (Array.isArray(response)) {
        $scope.districts = response;
    } else {
        response.then(
            function (response) {
                $scope.districts = response.data;
            });
    }

    $scope.$on('EDIT_CONTACT', function (e, data) {
        $scope.contact = {
            name : data.name,
            phoneNumber : data.mobile,
            address : data.address,
            id : data.id
        };
        $scope.contact.district = districtFactory.getDistrictFromName(data.district);
        $scope.isEditContact = true;
    });
    $scope.addContact = function () {
        if ($(addContactForm.contactName).hasClass('ng-valid') && $scope.contact.district !== undefined
            && $(addContactForm.contactNumber).hasClass('ng-valid') && $(addContactForm.contactAddress).hasClass('ng-valid')) {
            $scope.isChecked = false;
            $scope.working = true;
            var contact = $scope.contact;
            var user = $scope.loggedUser;
            user.contacts.addContact(contact.district.id, contact.district.district, contact.name, contact.address, contact.phoneNumber, user.access_token,
                function (result, errorCode) {
                    if (result) {
                        $scope.progressSucceed();
                        $scope.working = false;
                        //kissmetricsHelper.recordEvent('Kişi ekledi');
                        $timeout(function () {
                            $scope.closeSection();
                        }, 800);
                    } else {
                        errorMessages.getErrorMessage(errorCode, function(errorMessage){
                            $scope.processSuccess = false;
                            $scope.isChecked = true;
                            $scope.working = false;
                            $scope.errorMessage = errorMessage;
                        });
                    }
                }
            );
        } else {
            errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                $scope.working = false;
                $scope.isChecked = true;

                if( !($(addContactForm.contactName).hasClass('ng-valid')) )
                    otherExceptions.sendException("Add Contact",  "Eksik Bilgi- kişi ismi");
                else if( !($(addContactForm.contactNumber).hasClass('ng-valid')) )
                    otherExceptions.sendException("Add Contact",  "Eksik Bilgi- kişi numarası");
                else if( $scope.contact.district === undefined )
                    otherExceptions.sendException("Add Contact",  "Eksik Bilgi- kişi bölgesi");
                else if( !($(addContactForm.contactAddress).hasClass('ng-valid')) )
                    otherExceptions.sendException("Add Contact",  "Eksik Bilgi- kişi adresi");
                else
                    otherExceptions.sendException("Add Contact",  "Eksik Bilgi");

                $scope.errorMessage = errorMessage;
            });
        }
    };
    $scope.editContact = function () {
        if ($(addContactForm.contactName).hasClass('ng-valid') && $scope.contact.district !== undefined
            && $(addContactForm.contactNumber).hasClass('ng-valid') && $(addContactForm.contactAddress).hasClass('ng-valid')) {
            $scope.isChecked = false;
            $scope.working = true;
            var contactObj = $scope.contact;
            var user = $scope.loggedUser;
            user.contacts.updateContact(contactObj.district.id, contactObj.district, contactObj.id, contactObj.name, contactObj.address, contactObj.phoneNumber, user.access_token,
                function (result,errorCode) {
                    if (result) {
                        $scope.working = false;
                        $scope.progressSucceed();
                        //_kmq.push(['trackSubmit', 'addContactForm', 'contact edited']);
                        $timeout(function () {
                            $scope.contacts = $scope.loggedUser.contacts.getContacts();
                            $scope.closeSection();
                        }, 800);
                    } else {
                        errorMessages.getErrorMessage(errorCode, function(errorMessage){
                            $scope.processSuccess = false;
                            $scope.isChecked = true;
                            $scope.working = false;
                            $scope.errorMessage = errorMessage;
                        });
                    }
                }
            );
        } else {
            errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                $scope.working = false;
                $scope.isChecked = true;

                if( !($(addContactForm.contactName).hasClass('ng-valid')) )
                    otherExceptions.sendException("Edit Contact",  "Eksik Bilgi- kişi ismi");
                else if( !($(addContactForm.contactNumber).hasClass('ng-valid')) )
                    otherExceptions.sendException("Edit Contact",  "Eksik Bilgi- kişi numarası");
                else if( $scope.contact.district === undefined )
                    otherExceptions.sendException("Edit Contact",  "Eksik Bilgi- kişi bölgesi");
                else if( !($(addContactForm.contactAddress).hasClass('ng-valid')) )
                    otherExceptions.sendException("Edit Contact",  "Eksik Bilgi- kişi adresi");
                else
                    otherExceptions.sendException("Edit Contact",  "Eksik Bilgi");

                $scope.errorMessage = errorMessage;
            });
        }
    };
    $scope.deleteContact = function () {
        if ($scope.contact.id !== undefined) {
            $scope.loggedUser.contacts.removeContact($scope.contact.id, $scope.loggedUser.access_token,
                function (result,errorCode) {
                    if (result) {
                        $scope.isChecked = false;
                        //_kmq.push(['record', 'contact deleted']);
                        $timeout(function () {
                            $scope.contacts = $scope.loggedUser.contacts.getContacts();
                            $scope.setSignSection('profil');
                            $scope.resetForm($scope.addContactForm);
                            $scope.contact = {};
                            $scope.isEditContact = false;
                            $scope.$apply();
                        }, 1000);
                    } else {
                        errorMessages.getErrorMessage(errorCode, function(errorMessage){
                            $scope.processSuccess = false;
                            $scope.isChecked = true;
                            $scope.errorMessage = errorMessage;
                        });
                    }
                });
        }
    };

    $scope.closeSection = function (){
        $scope.resetForm($scope.addContactForm);
        $scope.contact = {};
        $scope.isEditContact = false;
    };
});
menu.controller('PasswordRetrievalCtrl', function ($scope, $timeout, userAccount,otherExceptions,errorMessages) {
    $scope.isChecked = false;
    $scope.processSuccess = false;
    $scope.isErrorHappened = false;

    $scope.passwordRetrieval = function () {
        if ($(passwordRetrievalForm.userMailRetrieval).hasClass('ng-valid')) {
            $scope.isChecked = false;

            userAccount.passwordRetrieval($scope.userMailRetrieval.email,function(result,errorCode){

                if (result) {
                    $scope.isChecked = false;
                    $scope.processSuccess = true;
                    $scope.isErrorHappened = false;
                    //_kmq.push(['trackSubmit', 'passwordRetrievalForm', 'password retrievaled']);
                } else {
                    errorMessages.getErrorMessage(errorCode, function(errorMessage){
                        $scope.processSuccess = false;
                        $scope.isChecked = true;
                        $scope.errorMessage = errorMessage;
                        $scope.isErrorHappened = true;
                    });

                }
            });
        } else {
            errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                $scope.isChecked = true;
                otherExceptions.sendException("Password Retrieval",  "Eksik Bilgi - şifre");
                $scope.errorMessage = errorMessage;
            });
        }
    }
});
menu.controller('BillingCtrl', function ($scope,$rootScope, $timeout,$document,textareaHelper, userAccount,otherExceptions,errorMessages) {
    $scope.isChecked = false;
    $scope.errorMessage = "";
    textareaHelper.checkMaxLength();

    $document.ready(function () {
        $('input.Billing').iCheck({

            checkboxClass : 'icheckbox_square-red',
            radioClass : 'iradio_flat-red'
        });
        $('.personalBilling')
            .on('ifChecked', function (event) {
                $timeout(function(){
                    $scope.billingSection = 1;
                    $scope.isChecked = false;
                });
            })
            .on('ifUnchecked', function (event) {
                $timeout(function(){
                    $scope.billingSection = 2;
                    $scope.isChecked = false;
                });
            });
    });

    if ($scope.userBillingInfo === undefined) {
        $scope.userBillingInfo = {};
        $scope.billingSection = 1;
        $('input.personalBilling').iCheck('check');
    }
    else {
        if ($scope.userBillingInfo.billing_type === "1") {
            $('input.personalBilling').iCheck('check');
            $scope.billingSection = 1;
        } else {
            $('input.corporateBilling').iCheck('check');
            $scope.billingSection = 2;
        }
    }

    $scope.radioSelected = function(radioName){
        switch(radioName){
            case 'personal':{
                $timeout(function() {
                    $scope.billingSection = 1;
                    $scope.isChecked = false;
                    $('input.personalBilling').iCheck('check');
                });
                break;
            }
            case 'corporate':{
                $timeout(function() {
                    $scope.billingSection = 2;
                    $scope.isChecked = false;
                    $('input.corporateBilling').iCheck('check');
                });
                break;
            }
        }
    };

    $scope.updateBilling = function () {
        var billingInfo = $scope.userBillingInfo;
        if ($scope.billingSection === 2) {
            if ($(billingForm.corporateBillingAddress).hasClass('ng-valid') && $(billingForm.corporateBillingCompany).hasClass('ng-valid')
                && $(billingForm.corporateBillingTaxOffice).hasClass('ng-valid') && $(billingForm.corporateBillingTaxNo).hasClass('ng-valid')) {
                $scope.isChecked = false;
                userAccount.updateUserCorporateBilling(billingInfo.billing_address, billingInfo.company, billingInfo.tax_office, billingInfo.tax_no, callbackFunc);
            }
            else {
                errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                    $scope.isChecked = true;

                    if( !($(billingForm.corporateBillingAddress).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- adres");
                    else if( !($(billingForm.corporateBillingCompany).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- şirket");
                    else if(  !($(billingForm.corporateBillingTaxOffice).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- vergi daires");
                    else if( !($(billingForm.corporateBillingTaxNo).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- vergi numarası");
                    else
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi");
                    $scope.errorMessage = errorMessage;
                });
            }
        }
        else if ($scope.billingSection === 1) {
            if ($(billingForm.personalBillingTC).hasClass('ng-valid') && $(billingForm.personalBillingAddress).hasClass('ng-valid') && $(billingForm.personalBillingDistrict).hasClass('ng-valid') && $(billingForm.personalBillingCity).hasClass('ng-valid')) {
                $scope.isChecked = false;
                userAccount.updateUserPersonalBilling(billingInfo.tc, billingInfo.small_city, billingInfo.city, billingInfo.personal_address, callbackFunc);
            }
            else {
                errorMessages.getErrorMessageFromName("MISSING_INFO", function(errorMessage){
                    $scope.isChecked = true;

                    if( !($(billingForm.personalBillingTC).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- tc");
                    else if( !($(billingForm.personalBillingAddress).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- adress");
                    else if(  !($(billingForm.personalBillingDistrict).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- bölge");
                    else if( !($(billingForm.personalBillingCity).hasClass('ng-valid')) )
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi- şehir");
                    else
                        otherExceptions.sendException("Update Billing",  "Eksik Bilgi");
                    $scope.errorMessage = errorMessage;
                });
            }
        }
        function callbackFunc(result, errorCode) {
            if (result) {
                $scope.isChecked = false;
                $scope.processSuccess = true;
                $rootScope.$emit("BillingChanged");
                $timeout(function () {
                    $scope.processSuccess = false;
                    $scope.switchSection(2)
                }, 1000);
            } else {
                errorMessages.getErrorMessage(errorCode, function(errorMessage){
                    $scope.processSuccess = false;
                    $scope.isChecked = true;
                    $scope.errorMessage = errorMessage;
                });
            }
        }
    };
});
menu.controller('authenticationCtrl', function ($scope,$rootScope , Facebook,adwordsHelper,errorMessages, userAccount, analyticsHelper,$timeout) {
    $rootScope.working = {};

    $rootScope.$on('FACEBOOKLOGIN',function(){
            $scope.getLoginStatus();
    });

    $rootScope.getLoginStatus = function () {
        Facebook.login(function (response) {
            Facebook.api('/me?fields=id,email,first_name, last_name ', function (response) {
                $rootScope.working.isLogInProcess = true;
                var tempUserInfo = response;
                userAccount.facebookLogin(tempUserInfo.id, tempUserInfo.email, tempUserInfo.first_name, tempUserInfo.last_name, tempUserInfo.gender,
                    function (result, errorCode) {
                        if (result) {
                            $scope.processSuccess = true;
                            $scope.isChecked = false;
                            $rootScope.working.isLogInProcess = false;
                            $rootScope.facebookErrorMessage = "";

                            if (userAccount.isFBRegister()) {
                                analyticsHelper.sendEvent('signUp', 'FB');
                                //kissmetricsHelper.registered('FB', tempUserInfo.email);

                                $timeout(function () {
                                    adwordsHelper.signUpTrack();
                                }, 2000);
                            } else {
                                analyticsHelper.sendEvent('signIn', 'FB');
                                //kissmetricsHelper.loggedIn('FB', tempUserInfo.email);
                            }

                            $rootScope.$emit('USER_GOING_SALE');
                        } else {
                            errorMessages.getErrorMessage(errorCode, function (errorMessage) {
                                $scope.processSuccess = false;
                                $scope.isChecked = true;
                                $rootScope.facebookErrorMessage = errorMessage;
                                $rootScope.working.isLogInProcess = false;
                            });
                        }
                });
            });
        }, { scope : 'email' });
    };
});