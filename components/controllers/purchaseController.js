'use strict';

var purchaseModule = angular.module("purchase",
    [
        'menuModule',
        'footerModule',
        'ui.bootstrap',
        'ui.bootstrap.tpls',
        'ui.mask',
        'userPurchaseModule',
        'userAccountModule',
        'angularPayments'
    ]
);
purchaseModule.controller('purchaseController', function ($location, $scope, deviceDetector, $rootScope, $state, $cookies, momentHelper, textareaHelper, facebookhelper, districtFactory, translateHelper, errorMessages, purchaseModel, purchaseFlowerModel, $document, userPurchase, PageTagsFactory, userAccount, districtObj, analyticsHelper, $stateParams, $http, $timeout, $window, flowerFactory, $modal, deliveryTimesFactory, otherExceptions) {
    PageTagsFactory.setTags();
    PageTagsFactory.changeWebSiteVariable();
    $scope.isChecked = false;
    $scope.userContacts = [];
    $scope.receiverDates = [];
    $scope.isErrorHappened = false;
    $scope.receiverTimes = [];
    $scope.chosenContact = undefined;
    $scope.working = false;
    $scope.crossSellActive = false;
    $scope.selectedCrossSell = 0;
    $scope.cargoError = 0;
    $scope.sendingCargo= 0;

    $scope.goCheckOutFromCrossSell = function () {
        $scope.serverError = "";
        $scope.working = true;
        sentPurchaseValues(function (response) {
            $scope.working = false;
            if (response) {
                translateHelper.getText('CHECKOUT_URL_PAYMENT', function (paymentUrl) {
                    $scope.incrementSectionProgress();
                    $scope.isChecked = false;
                    $state.go('purchaseProcess', {'purchaseStep': paymentUrl});
                });
            } else {
                otherExceptions.sendException("note-purchase", "Kullanıcı Bilgilerini Gönderirken Hata Oldu");
            }
        });
        $state.go('purchaseProcess', {'purchaseStep': 'odeme-bilgileri'});
    };

    function sentPurchaseValues(callback) {
        var paymentInfo = initPurchaseValues();
        addSenderNote(paymentInfo);

        var postUrl = "/save-data-before-sale";
        if ($scope.isUserLogin()) {
            postUrl = "/user-save-data-before-sale";
        }

        $http.post(webServer + postUrl, paymentInfo)
            .success(function (response) {
                $rootScope.receiver.saleNumber = response.sale_number;
                callback(true);
            }).error(function (response) {
                errorMessages.getErrorMessage(response.description, function (errorMessage) {
                    $scope.serverError = errorMessage;
                    callback(false);
                });
            });
    }

    function initPurchaseValues() {
        if ($rootScope.receiver.time) {
            var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.start_hour;
            var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.end_hour;
        }
        else {
            var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].start_hour;
            var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].end_hour;
        }
        var receiverDateStart = moment(sendingTimeStart, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');

        var receiveDateEnd = moment(sendingTimeEnd, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
        var paymentInfo = {
            "product_id": $scope.flower.id,
            "product_name": $scope.flower.name,
            "web_site_id": 1,
            "city_id": $scope.flower.sendingDistrict.id,
            "city_name": $scope.flower.sendingDistrict.district,
            "mail": $rootScope.sender.email,
            "name": $rootScope.sender.name,
            "wanted_delivery_date": receiverDateStart,
            "wanted_delivery_date_end": receiveDateEnd,
            "contact_name": $rootScope.receiver.name,
            "contact_address": $rootScope.receiver.address,
            "contact_mobile": $rootScope.receiver.phoneNumber,
            "browser": deviceDetector.getBrowserInfo().browser + "/" + deviceDetector.getBrowserInfo().version,
            "device": deviceDetector.isMobile() ? "1/" + deviceDetector.getMobileInfo() : "0",
            "lang_id": translateHelper.getCurrentLang()
        };
        if ($rootScope.sender.id) {
            paymentInfo.access_token = $rootScope.sender.access_token;
        }

        if ($rootScope.receiver.contact_id) {
            paymentInfo.contact_id = $rootScope.receiver.contact_id;
        }

        return paymentInfo;
    }

    function addSenderNote(paymentInfo) {
        if ($rootScope.note.card_message !== undefined)
            paymentInfo.card_message = $rootScope.note.card_message;
        if ($rootScope.note.customer_sender_name !== undefined)
            paymentInfo.customer_sender_name = $rootScope.note.customer_sender_name;
        if ($rootScope.note.customer_receiver_name !== undefined)
            paymentInfo.customer_receiver_name = $rootScope.note.customer_receiver_name;
    }

    if ($rootScope.chosenCrossProduct) {
        $scope.selectedCrossSell = $rootScope.chosenCrossProduct.id;
    }

    $scope.flower = purchaseFlowerModel.getFlower();

    if ($scope.flower.id === undefined)
        $state.go('landing');

    $timeout(function () {
        var tempMail = "";
        if (userAccount.checkUserLoggedin()) {
            tempMail = userAccount.getUserMail();
            tempMail.toLowerCase();
        }
        analyticsHelper.sendCriteoBucket($scope.flower.id, $scope.flower.price, tempMail, $scope.flower.city_id );
    }, 1000);
    $rootScope.$on('USER_SIGN_OUT', function () {
        if ($state.params.purchaseStep === 'kime' || $stateParams.orderId !== undefined) {
            $state.go('purchaseProcess', {'purchaseStep': ''});
        }

        location.reload();
    });
    $rootScope.$on('USER_SIGN_IN', function () {
        if ($state.params.purchaseStep === 'kime' || $stateParams.orderId !== undefined) {
            $state.go('purchaseProcess', {'purchaseStep': ''});
        }

        location.reload();
    });

    $document.ready(function () {
        $('.icheckField input').iCheck({
            checkboxClass: 'icheckbox_square-red',
            radioClass: 'icheckbox_square-red'
        });
    });

    /****   utility functions for scope     ****/
    $scope.isUserLogin = function () {
        return $rootScope.loggedUser !== undefined;
    };

    $scope.showPriceWithTroy = function(){

        return replaceString(parseFloat( $scope.flower.newPrice - 30 ).toFixed(2),'.', ',');

    };

    $scope.showPriceSpecialCampaign = function() {

        return replaceString(parseFloat( $scope.flower.priceWithDiscount - 30 ).toFixed(2),'.', ',');

    };

    $scope.getAllMoneyWithTroy = function (){

        if ($rootScope.chosenCampaign) {
            if ($rootScope.chosenCampaign.special_type == 1) {
                return replaceString(parseFloat( (parseFloat($rootScope.chosenCrossProduct.newPrice) + parseFloat($scope.flower.priceWithDiscount) - 30 ).toFixed(2) ).toFixed(2),'.', ',');
            }
        }

        return replaceString(parseFloat( (parseFloat($rootScope.chosenCrossProduct.newPrice) + parseFloat($scope.flower.newPrice) - 30 ).toFixed(2) ).toFixed(2),'.', ',');

    };

    $scope.getAllMoney = function () {

        if ($rootScope.chosenCampaign) {
            if ($rootScope.chosenCampaign.special_type == 1) {

                return replaceString(String((parseFloat($rootScope.chosenCrossProduct.newPrice) + parseFloat($scope.flower.priceWithDiscount)).toFixed(2)), ".", ",");
            }
        }

        return replaceString(String((parseFloat($rootScope.chosenCrossProduct.newPrice) + parseFloat($scope.flower.newPrice)).toFixed(2)), ".", ",");
    };

    $scope.hideCreditCard = function () {
        if ($rootScope.chosenCampaign) {
            if ($rootScope.chosenCampaign.type == 2) {
                return false;
            }
            else {
                if ($rootScope.chosenCrossProduct) {

                    if ($rootScope.chosenCampaign.special_type == 1) {

                        /*if ($rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 118) > 0) {
                            $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 118);
                        }
                        else {
                            $rootScope.couponForCrossSell = 0;
                        }*/

                        if (parseFloat($scope.flower.price) / 100 * 108 + parseFloat($rootScope.chosenCrossProduct.price) / 100 * 108 < $rootScope.chosenCampaign.value) {
                            return true;
                        }
                        else {
                            return false;
                        }

                    }
                    else {

                        if (parseFloat($scope.flower.price) + parseFloat($rootScope.chosenCrossProduct.price) < $rootScope.chosenCampaign.value) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                }
                else {

                    if ($scope.flower.product_type == 2) {
                        if (parseFloat($scope.flower.price) / 100 * 108 < $rootScope.chosenCampaign.value) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                    else if ($scope.flower.product_type == 3) {
                        if (parseFloat($scope.flower.price) / 100 * 118 < $rootScope.chosenCampaign.value) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }
                    else {
                        if (parseFloat($scope.flower.price) / 100 * 108 < $rootScope.chosenCampaign.value) {
                            return true;
                        }
                        else {
                            return false;
                        }
                    }

                    /*if (parseFloat($scope.flower.price) < $rootScope.chosenCampaign.value) {
                        return true;
                    }
                    else {
                        return false;
                    }*/
                }
            }
        }
        else {
            return false;
        }
        //!((chosenCampaign.value < flower.price && chosenCampaign.type == 1) || chosenCampaign.type == 2 || !chosenCampaign)
        //return replaceString( String((parseFloat( $rootScope.chosenCrossProduct.newPrice) + parseFloat($scope.flower.newPrice)).toFixed(2)) , ".", ",") ;
    };

    $scope.addProduct = function (productId) {
        $scope.selectedCrossSell = productId;
        $rootScope.crossSell.forEach(function (oneProduct) {
            if (productId == oneProduct.id) {
                oneProduct.crossSellPriceWithCoupon = 0;
                oneProduct.couponForCrossSell = 0;
                if ($rootScope.couponForCrossSell > 0) {
                    if ($rootScope.chosenCampaign) {
                        if ($rootScope.chosenCampaign.special_type == 1) {
                            $rootScope.crossSellPriceWithCoupon = (oneProduct.price / 100 * 108) - $rootScope.couponForCrossSell;
                            //console.log(oneProduct.price / 100 * 108);
                            if ($rootScope.crossSellPriceWithCoupon < 0) {
                                $rootScope.crossSellPriceWithCoupon = 0.0;
                            }
                            oneProduct.crossSellPriceWithCoupon = $rootScope.crossSellPriceWithCoupon;
                            oneProduct.couponForCrossSell = $rootScope.couponForCrossSell;

                            oneProduct.newPrice = oneProduct.crossSellPriceWithCoupon;
                            //oneProduct.newPrice = parseFloat(oneProduct.crossSellPriceWithCoupon / 100 * 108);
                            oneProduct.allTotal = oneProduct.newPrice + $scope.flower.newPrice;
                            $rootScope.chosenCrossProduct = oneProduct;
                        }
                        else {
                            oneProduct.newPrice = parseFloat(oneProduct.price / 100 * 108);
                            oneProduct.allTotal = oneProduct.newPrice + $scope.flower.newPrice;
                            $rootScope.chosenCrossProduct = oneProduct;
                        }
                    }
                    else {
                        oneProduct.newPrice = parseFloat(oneProduct.price / 100 * 108);
                        oneProduct.allTotal = oneProduct.newPrice + $scope.flower.newPrice;
                        $rootScope.chosenCrossProduct = oneProduct;
                    }
                }
                else {
                    oneProduct.newPrice = parseFloat(oneProduct.price / 100 * 108);
                    oneProduct.allTotal = oneProduct.newPrice + $scope.flower.newPrice;
                    $rootScope.chosenCrossProduct = oneProduct;
                }

            }
        });
    };

    $scope.removeProduct = function () {
        $scope.selectedCrossSell = 0;
        $rootScope.chosenCrossProduct = undefined;
    };

    $scope.checkUserLoggedIn = function () {
        return userAccount.checkUserLoggedin();
    };
    $scope.incrementSectionProgress = function () {
        $rootScope.sectionProgress++;
    };
    $scope.isSection = function (section) {
        return $scope.section === section;
    };
    $scope.isActive = function (activeSection) {
        return $scope.section >= activeSection;
    };
    $scope.setContact = function (ContactObj) {
        if (ContactObj.isActive === false) {
            setContactInfo(ContactObj);
        } else {
            setContactInfo();
        }
    };

    $scope.setCampaignInfo = function (campaign) {
        var price = 0.0;

        if (campaign !== null && ( $scope.flower.company_product == 0 || $scope.flower.product_type == 2 )) {
            if ($rootScope.chosenCampaign !== undefined && $rootScope.chosenCampaign.id === campaign.id && $scope.section === 3) {
                price = fixPrice($scope.flower.price);
            } else {
                price = calculatePriceFromCoupon(campaign.type, campaign.value, campaign.special_type);
            }
        } else {
            price = fixPrice($scope.flower.price);
        }

        $scope.flower.priceWithDiscount = price;

        if ($scope.flower.product_type == 2) {
            $scope.flower.newPrice = parseFloat(price / 100 * 108);
        }
        else if ($scope.flower.product_type == 3) {
            $scope.flower.newPrice = parseFloat(price / 100 * 118);
        }
        else {
            $scope.flower.newPrice = parseFloat(price / 100 * 108);
        }

        //$scope.flower.newPrice = parseFloat(price / 100 * 118);
        sessionStorage.flower = JSON.stringify($scope.flower);

    };
    function setContactInfo(contactObj) {
        $rootScope.receiver.name = contactObj !== undefined ? contactObj.name : undefined;
        $rootScope.receiver.lastName = contactObj !== undefined ? contactObj.surname : undefined;
        $rootScope.receiver.phoneNumber = contactObj !== undefined ? parseInt(contactObj.mobile) : undefined;
        $rootScope.receiver.address = contactObj !== undefined ? contactObj.address : undefined;
        $rootScope.receiver.contact_id = contactObj !== undefined ? contactObj.id : undefined;

        if (contactObj !== undefined) {
            $scope.districts.forEach(function (district) {
                if (contactObj.district == district.district) {
                    $scope.setDistrict(district);
                    flowerFactory.setSendingDistrict(district);
                    return;
                }
            });
        }
    }

    function calculatePriceFromCoupon(couponType, couponValue, special) {
        var price = 0.0;

        switch (couponType) {
            case "1":
            {

                if (special == 1) {
                    price = fixPrice($scope.flower.price) - parseInt(couponValue);

                    if ($scope.flower.product_type == 2) {
                        //$scope.flower.price = parseFloat($scope.flower.price / 100 * 108);

                        price = fixPrice(parseFloat($scope.flower.price / 100 * 108)) - parseInt(couponValue);

                    }
                    else if ($scope.flower.product_type == 3) {
                        //$scope.flower.price = parseFloat($scope.flower.price / 100 * 108);

                        price = fixPrice(parseFloat($scope.flower.price / 100 * 118)) - parseInt(couponValue);

                    }
                    else {
                        //$scope.flower.price = parseFloat($scope.flower.price / 100 * 118);

                        price = fixPrice(parseFloat($scope.flower.price / 100 * 108)) - parseInt(couponValue);
                    }

                }
                else {
                    price = fixPrice($scope.flower.price) - parseInt(couponValue);
                }

                if (price <= 0)
                    price = 0;
                break;
            }
            case "2":
            {
                var flowerPrice = fixPrice($scope.flower.price);

                if ($scope.flower.product_type == 2 || $scope.flower.product_type == 3) {
                    price = flowerPrice
                }
                else {
                    price = flowerPrice - (flowerPrice / 100 * parseInt(couponValue));
                }

                break;
            }
            default :
            {
                price = fixPrice($scope.flower.price);
            }
        }

        return price;
    }

    $scope.newContactSelected = function (contact) {
        $scope.setContact(contact);

        if ($scope.chosenContact !== undefined && ($scope.chosenContact.id !== contact.id)) {
            $scope.chosenContact.isActive = false;
        }

        $rootScope.sender.chosenContact = $scope.chosenContact = contact;
        contact.isActive = !contact.isActive;
    };
    $scope.useCampaign = function (campaign) {
        if ($scope.flower.speciality == 1) {

        }
        else {

            $scope.setCampaignInfo(campaign);
            if (campaign !== null) {
                if ($scope.section === 3 || $scope.section === 4) {
                    if (($rootScope.chosenCampaign !== undefined) && $rootScope.chosenCampaign.id !== campaign.id) {
                        $rootScope.chosenCampaign.isActive = false;
                    }

                    if ($rootScope.chosenCampaign !== undefined && $rootScope.chosenCampaign.id === campaign.id) {
                        $rootScope.chosenCampaign = undefined;
                    } else {
                        $rootScope.chosenCampaign = campaign;
                        //kissmetricsHelper.usedCoupon($rootScope.chosenCampaign.name, $rootScope.chosenCampaign.value, $rootScope.chosenCampaign.type);
                        $cookies.putObject('usedCoupon', $rootScope.chosenCampaign);
                    }

                    campaign.isActive = !campaign.isActive;
                }
            }
            else {
                $rootScope.chosenCampaign = undefined;

                if ($rootScope.chosenCrossProduct) {
                    $rootScope.chosenCrossProduct.newPrice = parseFloat($rootScope.chosenCrossProduct.price / 100 * 108);
                    $rootScope.chosenCrossProduct.allTotal = $rootScope.chosenCrossProduct.newPrice + $scope.flower.newPrice;
                    //$rootScope.chosenCrossProduct = $rootScope.chosenCrossProduct;
                    $rootScope.chosenCrossProduct.crossSellPriceWithCoupon = 0;
                    $rootScope.chosenCrossProduct.couponForCrossSell = 0;
                }
            }
            if ($rootScope.chosenCampaign) {
                if ($rootScope.chosenCampaign.special_type == 1) {

                    if ($scope.flower.product_type == 2) {
                        //$scope.flower.price = parseFloat($scope.flower.price / 100 * 108);

                        if ($rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108) > 0) {
                            $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108);
                        }
                        else {
                            $rootScope.couponForCrossSell = 0;
                        }

                    }
                    else if ($scope.flower.product_type == 3) {
                        //$scope.flower.price = parseFloat($scope.flower.price / 100 * 108);

                        if ($rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 118) > 0) {
                            $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 118);
                        }
                        else {
                            $rootScope.couponForCrossSell = 0;
                        }

                    }
                    else {
                        //$scope.flower.price = parseFloat($scope.flower.price / 100 * 118);

                        if ($rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108) > 0) {
                            $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108);
                        }
                        else {
                            $rootScope.couponForCrossSell = 0;
                        }
                    }

                    /*if( $rootScope.chosenCampaign.value - $scope.flower.price > 0 ){
                     $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - $scope.flower.price;
                     }
                     else{
                     $rootScope.couponForCrossSell = 0;

                     }*/
                }
            }

            if ($rootScope.chosenCampaign == undefined) {
                if ($rootScope.chosenCrossProduct) {
                    $rootScope.chosenCrossProduct.newPrice = parseFloat($rootScope.chosenCrossProduct.price / 100 * 108);
                    $rootScope.chosenCrossProduct.allTotal = $rootScope.chosenCrossProduct.newPrice + $scope.flower.newPrice;
                    //$rootScope.chosenCrossProduct = $rootScope.chosenCrossProduct;
                    $rootScope.chosenCrossProduct.crossSellPriceWithCoupon = 0;
                    $rootScope.chosenCrossProduct.couponForCrossSell = 0;
                }
            }
            else {
                if ($rootScope.chosenCampaign.special_type == 0) {
                    if ($rootScope.chosenCrossProduct) {
                        $rootScope.chosenCrossProduct.newPrice = parseFloat($rootScope.chosenCrossProduct.price / 100 * 108);
                        $rootScope.chosenCrossProduct.allTotal = $rootScope.chosenCrossProduct.newPrice + $scope.flower.newPrice;
                        //$rootScope.chosenCrossProduct = $rootScope.chosenCrossProduct;
                        $rootScope.chosenCrossProduct.crossSellPriceWithCoupon = 0;
                        $rootScope.chosenCrossProduct.couponForCrossSell = 0;
                    }
                }
                else {
                    if ($rootScope.couponForCrossSell > 0) {
                        if ($rootScope.chosenCrossProduct) {
                            $rootScope.crossSellPriceWithCoupon = $rootScope.chosenCrossProduct.price / 100 * 108 - $rootScope.couponForCrossSell;
                            if ($rootScope.crossSellPriceWithCoupon < 0) {
                                $rootScope.crossSellPriceWithCoupon = 0.0;
                            }
                            $rootScope.chosenCrossProduct.crossSellPriceWithCoupon = $rootScope.crossSellPriceWithCoupon;
                            $rootScope.chosenCrossProduct.couponForCrossSell = $rootScope.couponForCrossSell;

                            $rootScope.chosenCrossProduct.newPrice = $rootScope.chosenCrossProduct.crossSellPriceWithCoupon;
                            //$rootScope.chosenCrossProduct.newPrice = parseFloat($rootScope.chosenCrossProduct.crossSellPriceWithCoupon / 100 * 108);
                            $rootScope.chosenCrossProduct.allTotal = $rootScope.chosenCrossProduct.newPrice + $scope.flower.newPrice;
                            //$rootScope.chosenCrossProduct = $rootScope.chosenCrossProduct;
                        }
                    }
                }
            }

        }

        if(  parseFloat( $scope.flower.newPrice )  < 100 && !$scope.chosenCrossProduct ){
            $scope.tempStringTroy = '';
            //console.log('girdi');
        }
        else if( $rootScope.tempStringIsTroyCard ){
            $scope.tempStringTroy = ' - TROY indirimi 30 TL';
            //console.log($scope.flower.newPrice);
            $scope.troyPrice =  parseFloat( $scope.flower.newPrice  ) - 30;
        }
        else{
            $scope.tempStringTroy = '';
        }

        $rootScope.tempStringTroyRoot = $scope.tempStringTroy;

    };
    $scope.getReceiveDateForServer = function () {
        if ($rootScope.receiver !== undefined && $rootScope.receiver.date !== undefined && $rootScope.receiver.time !== undefined) {
            var momentObj = moment($rootScope.receiver.date.value, 'DD-MM-YYYY');
            momentObj.hour($rootScope.receiver.time.value);
            var receiverDate = momentObj.format('YYYY-MM-DD HH:mm:ss');
            return receiverDate;
        }
    };

    /*****  input watch functions  ***/
    $scope.$watch('receiver.name', function () {
        if ($scope.chosenContact !== undefined && ($scope.chosenContact.name !== $rootScope.receiver.name)) {
            $rootScope.receiver.contact_id = undefined;
            $scope.chosenContact.isActive = false;
            $rootScope.isNewContact = false;
        }
    });
    $scope.$watch('receiver.lastName', function () {
        if ($scope.chosenContact !== undefined && ($scope.chosenContact.surname !== $rootScope.receiver.lastName)) {
            $rootScope.receiver.contact_id = undefined;
            $scope.chosenContact.isActive = false;
            $rootScope.isNewContact = false;
        }
    });

    /***   input control functions  ****/
    $scope.checkReceiver = function () {

        if ($rootScope.receiver.date !== undefined && $rootScope.receiver.time !== undefined && $(receiverForm.contact_name).hasClass('ng-valid')
            && $(receiverForm.contact_mobile).hasClass('ng-valid') && $(receiverForm.contact_address).hasClass('ng-valid') && $rootScope.sender.name !== undefined && $rootScope.sender.email !== undefined) {

            if ($rootScope.isNewContact) {
                purchaseModel.addContact($scope.flower.sendingDistrict);
                $rootScope.isNewContact = false;
            }

            var tempNameList = $rootScope.receiver.name.split(" ");
            var tempCompleteName = "";
            for (var x = 0; x < tempNameList.length; x++) {
                tempCompleteName += tempNameList[x].charAt(0).toUpperCase() + tempNameList[x].slice(1) + " ";
            }

            $rootScope.receiver.name = tempCompleteName;

            var sendingTime = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.start_hour;
            var momentObj = moment(sendingTime, 'DD-MM-YYYY HH:mm');
            momentObj.minute(parseInt($rootScope.receiver.date.extra_minutes));

            //if (moment().isBefore(momentObj)) {
                $scope.serverError = "";
                $scope.incrementSectionProgress();
                $scope.isChecked = false;

                if ($rootScope.sender.id) {
                    $scope.initUserWorking = true;
                    momentObj.subtract(1, 'hours');
                    var receiverDateStart = momentObj.format('YYYY-MM-DD HH:mm:ss');

                    purchaseModel.sendUserPassedReceiverDatas(receiverDateStart, $scope.flower.sendingDistrict.district, $scope.flower.name, function (result) {
                        if (result) {
                            translateHelper.getText('CHECKOUT_URL_NOT', function (paymentUrl) {
                                $state.go('purchaseProcess', {'purchaseStep': paymentUrl});
                                $scope.initUserWorking = false;
                            });
                        } else {
                            errorMessages.getErrorMessage(result.description, function (errorMessage) {
                                $scope.serverError = errorMessage;
                                otherExceptions.sendException("check-receiver", "Sepet terk bilgilerini gönderirken hata");
                                $scope.initUserWorking = false;
                            });
                        }
                    });
                } else {
                    translateHelper.getText('CHECKOUT_URL_NOT', function (paymentUrl) {
                        $state.go('purchaseProcess', {'purchaseStep': paymentUrl});
                    });
                }
            //} else {
            //    $scope.isChecked = true;
            //    errorMessages.getErrorMessageFromName("PASSED_DATE_SALE", function (errorMessage) {
            //        $scope.serverError = errorMessage;
            //        otherExceptions.sendException("receiver-purchase", "Gönderim Aralığı Geçilmiş");
            //    });
            //}
        } else {
            errorMessages.getErrorMessageFromName("MISSING_INFO", function (errorMessage) {
                $scope.isChecked = true;

                if ($rootScope.receiver.date === undefined)
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- tarih");
                else if ($rootScope.receiver.time === undefined)
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- zaman");
                else if (!($(receiverForm.contact_name).hasClass('ng-valid')))
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- alıcı ismi");
                else if (!($(receiverForm.contact_mobile).hasClass('ng-valid')))
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- alıcı numarası");
                else if (!($(receiverForm.contact_address).hasClass('ng-valid')))
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- alıcı adresi");
                else if ($rootScope.sender.name === undefined)
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- gönderen adı");
                else if ($rootScope.sender.name !== undefined)
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi- gönderen mail");
                else
                    otherExceptions.sendException("receiver-purchase", "Eksik Bilgi");

                $scope.serverError = errorMessage;
            });
        }
    };

    /***   purchase complete functions ***/

    function fixPrice(price) {
        var ret = replaceString(price, ",", ".");
        return parseFloat(ret);
    }

    /**    initialize scope functions  ***/

    function setDates() {

        //if( tempContinentId == 'Ups' ){
        //    tempContinentId = 'Avrupa-2';
        //}


        $scope.receiverDates = deliveryTimesFactory.getDeliveryTimesAsDisplayFormat($scope.flower.sendingDistrict.continent_id);

        var localeLang = translateHelper.getCurrentLang();
        $scope.setTimes();
        var tempFlag = true;
        var tempFlagTime = true;
        if ($rootScope.receiver.date) {
            $scope.receiverDates.forEach(function (time) {
                if (time.value == $rootScope.receiver.date.value) {
                    $rootScope.receiver.date = time;
                    tempFlag = false;
                }
            });
            $scope.receiverTimes.forEach(function (time) {
                if (time.timeStep == $rootScope.receiver.date.timeStep) {
                    $rootScope.receiver.time = time;
                }
            });
        }
        if (!$rootScope.receiver.time)
            switch (moment().locale(localeLang).day()) {
                case 6:
                {
                    if (tempFlag)
                        $rootScope.receiver.date = moment().isBefore(moment('14', 'HH')) ? $scope.receiverDates[1] : $scope.receiverDates[0];
                    $scope.setTimes();
                    if ($scope.receiverTimes.length < 2) {
                        $rootScope.receiver.time = $scope.receiverTimes[0];
                    }
                    else
                        $rootScope.receiver.time = $scope.receiverTimes[1];
                    $rootScope.isDateNotSelected = false;
                    break;
                }
                case 0:
                {
                    if (tempFlag)
                        $rootScope.receiver.date = $scope.receiverDates[0];
                    $scope.setTimes();
                    if ($scope.receiverTimes.length < 2) {
                        $rootScope.receiver.time = $scope.receiverTimes[0];
                    }
                    else
                        $rootScope.receiver.time = $scope.receiverTimes[1];
                    $rootScope.isDateNotSelected = false;
                    break;
                }
                default :
                {
                    $rootScope.isDateNotSelected = false;
                    if (moment().isBefore(moment('14', 'HH'))) {
                        if (tempFlag)
                            $rootScope.receiver.date = $scope.receiverDates[0];
                        $scope.setTimes();
                        //if($scope.receiverTimes.length < 2){
                        $rootScope.receiver.time = $scope.receiverTimes[0];
                        if (moment().isBefore(moment($scope.receiverDates[0].value.split('-')[0], 'DD'))) {
                            $rootScope.isDateNotSelected = true;
                            $rootScope.receiver.time = "";
                            $rootScope.receiver.date = "";
                        }
                        //}
                        //else
                        //    $rootScope.receiver.time = moment().isBefore(moment('10', 'HH')) ? $scope.receiverTimes[1] : $scope.receiverTimes[0];
                    } else {
                        //if(tempFlag)
                        //$rootScope.receiver.date = moment().isSame(moment($scope.receiverDates[0].value, 'DD-MM-YYYY'), 'day') ? $scope.receiverDates[1] : $scope.receiverDates[0];
                        //$scope.setTimes();
                        //$rootScope.receiver.time = $scope.receiverTimes[0];
                        $rootScope.isDateNotSelected = true;
                        $rootScope.receiver.time = "";
                        $rootScope.receiver.date = "";
                    }
                    break;
                }
            }


        if($scope.flower.sendingDistrict.continent_id == 'Ups'){
            $rootScope.receiver.date = $scope.receiverDates[0];
            $rootScope.receiver.time = $scope.receiverTimes[0];
        }
    }

    $scope.dateChanged = function () {
        $rootScope.isDateNotSelected = true;
        $scope.setTimes();
    };

    $scope.setTimes = function () {
        if ($scope.receiver.date !== undefined) {
            if ($rootScope.isDateNotSelected)
                $scope.receiver.time = undefined;
            $scope.receiverTimes = $scope.receiver.date.hours;
            if ($scope.receiverTimes)
                $scope.receiverTimes.forEach(function (time) {
                    time.timeStep = time.start_hour + " - " + time.end_hour;
                });
            $rootScope.isDateNotSelected = false;
        }

        if($scope.flower.sendingDistrict.continent_id == 'Ups'){
            $rootScope.receiver.time = $scope.receiverTimes[0];
        }

    };
    function getDate(dateValue) {
        for (var i = 0; i < $scope.receiverDates.length; i++) {
            if ($scope.receiverDates[i].value == dateValue)
                return $scope.receiverDates[i];
        }
    }

    function getTime(start_hour) {
        for (var i = 0; i < $scope.receiverTimes.length; i++) {
            if ($scope.receiverTimes[i].start_hour === start_hour)
                return $scope.receiverTimes[i];
        }
    }

    function setDistricts() {
        if (districtObj.data !== undefined)
            $scope.districts = districtObj.data;
        else
            $scope.districts = districtObj;

        var tempDistricts = $scope.districts.filter(function (el) {
            return el.city == $scope.flower.sendingDistrict.city && el.city_id == $scope.flower.sendingDistrict.city_id;
        });


        $scope.districts = tempDistricts;
    }

    $scope.expirationMonths = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
    $scope.expirationYears = ["18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "30"];
    $scope.paymentTypes = ['Kredi Kartı'];

    $scope.setDistrict = function (DistrictObj) {
        $scope.flower.sendingDistrict = DistrictObj;
        $rootScope.receiver.date = undefined;
        $rootScope.receiver.time = undefined;

        if( $scope.flower.cargo_sendable == 0 && DistrictObj.city_id == 3 ){
            $scope.cargoError = 1;
            $scope.sendingCargo = 0;
        }
        else if( $scope.flower.cargo_sendable == 1 && DistrictObj.city_id == 3  ){
            $scope.cargoError = 0;
            $scope.sendingCargo = 1;

        }
        else{
            $scope.sendingCargo = 0;
            $scope.cargoError = 0;
        }

        setDates();
        return DistrictObj.district;
    };

    scrollToTop();
    var isMember = 'non-member';


    purchaseModel.checkCrossSell(function (result) {
        if (result.status > 0) {
            //$scope.crossSellActive = true;
            //$rootScope.crossSell = result.data;
            //$('.barPoint').removeClass('col-lg-4').removeClass('col-md-4').removeClass('col-sm-4').removeClass('col-xs-4').removeClass('hidden');
            //$('.barPoint').addClass('col-lg-3').addClass('col-md-3').addClass('col-sm-3').addClass('col-xs-3');
            //$('#ulBar').removeClass('col-lg-8').removeClass('col-lg-offset-2').addClass('col-lg-11').addClass('col-lg-offset-1');

            if ($scope.flower.product_type == 2 || $scope.flower.product_type == 3) {
                $rootScope.chosenCrossProduct = null;
                $scope.crossSellActive = false;
                $scope.selectedCrossSell = 0;
                $rootScope.crossSell = [];
            }
            else {
                $scope.crossSellActive = true;
                $rootScope.crossSell = result.data;
                $('.barPoint').removeClass('col-lg-4').removeClass('col-md-4').removeClass('col-sm-4').removeClass('col-xs-4').removeClass('hidden');
                $('.barPoint').addClass('col-lg-3').addClass('col-md-3').addClass('col-sm-3').addClass('col-xs-3');
                $('#ulBar').removeClass('col-lg-8').removeClass('col-lg-offset-2').addClass('col-lg-11').addClass('col-lg-offset-1');
            }
        }
        else {
            $rootScope.chosenCrossProduct = null;
            $scope.crossSellActive = false;
            $scope.selectedCrossSell = 0;
            $rootScope.crossSell = [];
        }
    });
    switch ($state.params.purchaseStep) {
        case '':
            $rootScope.receiver = purchaseModel.getReceiver();
            $rootScope.note = purchaseModel.getNote();
            $rootScope.sender = purchaseModel.getSender();
            $rootScope.userCampaigns = [];
            $rootScope.chosenCampaign = undefined;
            $rootScope.isNewContact = false;
            translateHelper.getText('ADD_CONTACTS_TEXT', function (addContactsText) {
                $rootScope.newContactText = addContactsText;
            });
            $rootScope.isDateNotSelected = true;
            $rootScope.isSaleFail = false;
        //sessioncamHelper.pageChanged('/', 'satin-alma');
        // Intentionally no break here
        case 'kime':
        case 'to-who':
            if ($rootScope.receiver) {
                //analyticsHelper.sendCriteoBucket($scope.flower.id,$scope.flower.price );
                $scope.section = 1;
                $rootScope.sectionProgress = 1;

                var tempCityId = $scope.flower.city_id;

                deliveryTimesFactory.getDeliveryTimes($scope.flower.id,tempCityId , function () {
                    setDates();
                    $scope.setTimes();
                });
                setDistricts();

                if( $scope.flower.cargo_sendable == 0 && $scope.flower.sendingDistrict.continent_id === 'Ups' ){
                    $scope.cargoError = 1;
                    $scope.sendingCargo = 0;
                }
                else if( $scope.flower.cargo_sendable == 1 && $scope.flower.sendingDistrict.continent_id === 'Ups'  ){
                    $scope.cargoError = 0;
                    $scope.sendingCargo = 1;

                }
                else{
                    $scope.sendingCargo = 0;
                    $scope.cargoError = 0;
                }

                initUser(isMember);
                if ($scope.flower.speciality == 1) {
                    purchaseModel.setCouponsWithOnlyBulgari($scope.flower);
                    $rootScope.chosenCampaign = $rootScope.sender.chosenCampaign;
                }

                if ($scope.flower.company_product > 0 || $scope.flower.product_type == 2 || $scope.flower.product_type == 3) {
                    if ($rootScope.chosenCampaign) {
                        if ($rootScope.chosenCampaign.special_type == 1) {
                            $rootScope.userCampaigns = [];
                            $rootScope.userCampaigns[0] = $rootScope.chosenCampaign;
                            //$rootScope.chosenCampaign = [];
                        }
                        else {
                            $rootScope.userCampaigns = [];
                            $rootScope.chosenCampaign = [];
                            $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);

                            if ($scope.flower.product_type == 2) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                            else if ($scope.flower.product_type == 3) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                            }
                            else {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                        }
                    }
                    else {
                        $rootScope.userCampaigns = [];
                        $rootScope.chosenCampaign = [];
                        $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);

                        if ($scope.flower.product_type == 2) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                        else if ($scope.flower.product_type == 3) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                        }
                        else {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                    }

                }

                //console.log($scope.flower.priceWithDiscount);
                if($rootScope.chosenCampaign){
                    if ($scope.flower.product_type == 2) {
                        $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                    }
                    else if ($scope.flower.product_type == 3) {
                        $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                    }
                    else {
                        $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                    }
                }
                else{
                    $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                    if ($scope.flower.product_type == 2) {
                        $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                    }
                    else if ($scope.flower.product_type == 3) {
                        $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                    }
                    else {
                        $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                    }
                }

                sendGAEvent('Receiver Info');
                //kissmetricsHelper.sendPageView("Kime'yi");
                facebookhelper.trackEvent(facebookhelper.facebookAdTypes.ADD_TO_CART, {
                    content_name: $scope.flower.name,
                    value: $scope.flower.price,
                    content_ids : $scope.flower.id,
                    content_type : 'product',
                    currency: 'TRY'
                });
                break;
            } else {
                $state.go('purchaseProcess', {'purchaseStep': ''});
            }
        case 'not-ekle':
        case 'note':
            if ($rootScope.sectionProgress >= 2) {
                $scope.section = 2;
                //var sendingTimeStart = $scope.receiver.date.value + " " + $scope.receiver.time.start_hour;
                if ($rootScope.receiver.time) {
                    var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.start_hour;
                    //var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.end_hour;
                }
                else {
                    var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].start_hour;
                    //var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].end_hour;
                }
                var receiverDateStart = moment(sendingTimeStart, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
                initUserWithCoupon(isMember, $scope.flower.id, receiverDateStart);
                sendGAEvent('User Note');
                //kissmetricsHelper.sendPageView("Notu");
                if ($scope.flower.company_product > 0 || $scope.flower.product_type == 2 || $scope.flower.product_type == 3) {

                    if ($rootScope.chosenCampaign) {
                        if ($rootScope.chosenCampaign.special_type == 1) {
                            $rootScope.userCampaigns = [];
                            $rootScope.userCampaigns[0] = $rootScope.chosenCampaign;
                            //$rootScope.chosenCampaign = [];
                        }
                        else {
                            $rootScope.userCampaigns = [];
                            $rootScope.chosenCampaign = [];
                            $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                            console.log($scope.flower.priceWithDiscount);
                            //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

                            if ($scope.flower.product_type == 2) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                            else if ($scope.flower.product_type == 3) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                            }
                            else {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                        }
                    }
                    else {
                        $rootScope.userCampaigns = [];
                        $rootScope.chosenCampaign = [];
                        $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                        //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

                        if ($scope.flower.product_type == 2) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                        else if ($scope.flower.product_type == 3) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                        }
                        else {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                    }


                }
            } else
                $state.go('purchaseProcess', {'purchaseStep': ''});
            break;
        case 'tat-kat':
            if ($rootScope.sectionProgress >= 2.5) {
                $scope.section = 2.5;
                if ($rootScope.receiver.time) {
                    var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.start_hour;
                    //var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.end_hour;
                }
                else {
                    var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].start_hour;
                    //var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].end_hour;
                }
                //var sendingTimeStart = $scope.receiver.date.value + " " + $scope.receiver.time.start_hour;
                var receiverDateStart = moment(sendingTimeStart, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
                initUserWithCoupon(isMember, $scope.flower.id, receiverDateStart);
                sendGAEvent('Cross-Sell');
                //sendGAEvent('User Note');
                if ($scope.flower.company_product > 0 || $scope.flower.product_type == 2 || $scope.flower.product_type == 3) {

                    if ($rootScope.chosenCampaign) {
                        if ($rootScope.chosenCampaign.special_type == 1) {
                            $rootScope.userCampaigns = [];
                            $rootScope.userCampaigns[0] = $rootScope.chosenCampaign;
                            //$rootScope.chosenCampaign = [];
                        }
                        else {
                            $rootScope.userCampaigns = [];
                            $rootScope.chosenCampaign = [];
                            $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                            //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

                            if ($scope.flower.product_type == 2) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                            else if ($scope.flower.product_type == 3) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                            }
                            else {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                        }
                    }
                    else {
                        $rootScope.userCampaigns = [];
                        $rootScope.chosenCampaign = [];
                        $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                        //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

                        if ($scope.flower.product_type == 2) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                        else if ($scope.flower.product_type == 3) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                        }
                        else {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                    }
                }
            } else
                $state.go('purchaseProcess', {'purchaseStep': ''});
            break;
        case 'odeme-bilgileri':
        case 'payment':
            if ($rootScope.sectionProgress >= 3) {
                if ($rootScope.receiver.time) {
                    var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.start_hour;
                    //var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.end_hour;
                }
                else {
                    var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].start_hour;
                    //var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].end_hour;
                }
                //var sendingTimeStart = $scope.receiver.date.value + " " + $scope.receiver.time.start_hour;
                var receiverDateStart = moment(sendingTimeStart, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');
                $rootScope.userCampaigns = purchaseModel.setCouponsWithDate($scope.flower.id, receiverDateStart);
                if ($rootScope.loggedUser)
                    if ($rootScope.loggedUser.company_user && $rootScope.loggedUser.company_user != '0' && $rootScope.loggedUser.company_user != 0)
                        $scope.section = 4;
                    else
                        $scope.section = 3;
                else
                    $scope.section = 3;

                //kissmetricsHelper.sendPageView("Ödeme Bilgileri'ni");
                sendGAEvent('Payment');
                facebookhelper.trackEvent(facebookhelper.facebookAdTypes.ADD_PAYMENT_INFO, {
                    content_name: $scope.flower.name,
                    content_ids: "['" + $scope.receiver.saleNumber + "']",
                    value: $scope.flower.price,
                    content_type : 'product',
                    currency: 'TRY'
                });

                if ($rootScope.chosenCampaign) {
                    //kissmetricsHelper.usedCoupon($rootScope.chosenCampaign.name, $rootScope.chosenCampaign.value, $rootScope.chosenCampaign.type);
                    $cookies.putObject('usedCoupon', $rootScope.chosenCampaign);
                }
            }
            else {
                if ($stateParams.orderId === undefined)
                    $state.go('purchaseProcess', {'purchaseStep': ''});
                else {
                    if ($rootScope.loggedUser)
                        if ($rootScope.loggedUser.company_user && $rootScope.loggedUser.company_user != '0' && $rootScope.loggedUser.company_user != 0)
                            $scope.section = 4;
                        else
                            $scope.section = 3;
                    else
                        $scope.section = 3;
                    $rootScope.sectionProgress = $scope.section;
                    $http.get(webServer + '/sale-fail-data/' + $stateParams.orderId).success(function (response) {

                        if (response.data.payment_methods == 'OK') {
                            $location.path('/satis-ozet');
                        }
                        else {
                            $rootScope.isSaleFail = true;
                            initVariables(response.data);
                            initUser(isMember);
                            $scope.serverError = response.data.errorMessage;
                            //$rootScope.errorMessage = response.data.errorMessage;
                            $scope.addProduct(response.data.cross_sell_id);
                            $timeout(function () {
                                $scope.addProduct(response.data.cross_sell_id);
                            }, 3000);
                            //$rootScope.userCampaigns = purchaseModel.setCouponsWithDate( response.data.products_id , response.data.wanted_delivery_date );
                            sendGAEvent('Payment');
                        }

                        //sessioncamHelper.pageChanged('/odeme-bilgileri', 'satin-alma');
                        //kissmetricsHelper.sendPageView("Ödeme Bilgileri'ni");
                    }).error(function (data) {
                        $state.go('purchaseProcess', {'purchaseStep': ''});
                    })
                }
            }
            break;
    }

    function initVariables(data) {
        errorMessages.getErrorMessage(data.payment_methods, function (errorMessage) {
            $scope.serverError = errorMessage;
        });

        setDistricts();

        $modal.open({
            templateUrl: '../../views/bf-utility-pages/paymentFailed-v2.0.html',
            size: 'sm',
            controller: function ($scope, $modalInstance, $location) {
                errorMessages.getErrorMessage(data.payment_methods, function (errorMessage) {
                    $scope.error = data.errorMessage;
                });

                $scope.closeModel = function () {
                    $timeout(function () {
                        $modalInstance.close();
                        if (data.payment_methods == '430') {
                            $state.go('landing');
                        }
                        else if (data.payment_methods == 'OK') {
                            $location.path('/satis-ozet');
                        }
                    }, 1000);
                };
            }
        });

        $timeout(function () {
            if (data.products_id != $scope.flower.id) {
                //console.log(data);
                var flowerObj = flowerFactory.getFlower(data.products_id);
                purchaseFlowerModel.setFlower(flowerObj);
                $scope.flower = purchaseFlowerModel.getFlower();
            }

            if ($scope.flower.product_type == 2 || $scope.flower.product_type == 3) {
                initUserWithOutCoupon(isMember, data.products_id, data.wanted_delivery_date);
            }
            else {
                initUserWithCoupon(isMember, data.products_id, data.wanted_delivery_date);
            }

            deliveryTimesFactory.getDeliveryTimes(data.products_id,$scope.flower.city_id , function () {
                $rootScope.receiver = purchaseModel.setReceiver(data.contact_name, data.contact_mobile, data.contact_address, $stateParams.orderId, data.card_message);

                var tempNameList = $rootScope.receiver.name.split(" ");
                var tempCompleteName = "";
                for (var x = 0; x < tempNameList.length; x++) {
                    tempCompleteName += tempNameList[x].charAt(0).toUpperCase() + tempNameList[x].slice(1) + " ";
                }

                $rootScope.receiver.name = tempCompleteName;

                initDistrict(data);
                var currentLang = translateHelper.getCurrentLang();
                var momentObj = moment(data.wanted_delivery_date, 'YYYY-MM-DD HH:mm:ss').locale(currentLang);//momentHelper.getMomentObj('YYYY-MM-DD HH:mm:ss', data.wanted_delivery_date);
                $rootScope.receiver.date = getDate(momentObj.format('DD-MM-YYYY'));
                $rootScope.receiver.time = getTime(momentObj.format('HH:mm'));
                $rootScope.note = purchaseModel.setNote(data.customer_receiver_name, data.customer_sender_name, data.card_message);
                $rootScope.sender = purchaseModel.setSender(data.name, data.mobile, data.mail);
            });

            /*if ($scope.flower.product_type == 2) {
                $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 108);
            }
            else {
                $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 118);
            }*/
            //console.log($scope.flower.priceWithDiscount);
            if($scope.flower.priceWithDiscount){
                if ($scope.flower.product_type == 2) {
                    $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                }
                else if ($scope.flower.product_type == 3) {
                    $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                }
                else {
                    $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                }
            }

        }, 3000);

        /*$timeout(function () {
         if( $scope.flower.product_type == 2 ){
         $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 108);
         }
         else if( $scope.flower.product_type == 3 ){
         $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 118);
         }

         }, 6000);*/

    }

    function initDistrict(data) {
        $scope.districts.forEach(function (district) {
            if (data.city_id == district.id) {
                $scope.setDistrict(district);
                return;
            }
        });
    }

    function initUserWithCoupon(isMember, id, date) {
        $scope.initUserWorking = true;

        purchaseModel.initUserDate(date, function (sender, user, receiver) {
            if (sender.id !== undefined) {
                $rootScope.sender = sender;
                $rootScope.loggedUser = jQuery.extend(true, {}, user);
                $scope.userContacts = $rootScope.sender.userContacts;
                $rootScope.userCampaigns = purchaseModel.setCouponsWithDate(id, date);
                $rootScope.chosenCampaign = $rootScope.sender.chosenCampaign;
                $scope.chosenContact = $rootScope.sender.chosenContact;
                $rootScope.receiver = receiver;
                $scope.selectedContact = $scope.chosenContact;

                isMember = 'member';
                $scope.initUserWorking = false;
            } else {
                $scope.initUserWorking = false;
            }
        });

    }

    function initUserWithOutCoupon(isMember, id, date) {
        $scope.initUserWorking = true;

        purchaseModel.initUserDate(date, function (sender, user, receiver) {
            if (sender.id !== undefined) {
                $rootScope.sender = sender;
                $rootScope.loggedUser = jQuery.extend(true, {}, user);
                $scope.userContacts = $rootScope.sender.userContacts;
                $rootScope.userCampaigns = purchaseModel.setCouponsWithDate(id, date);
                $rootScope.chosenCampaign = $rootScope.sender.chosenCampaign;
                $scope.chosenContact = $rootScope.sender.chosenContact;
                $rootScope.receiver = receiver;
                $scope.selectedContact = $scope.chosenContact;

                isMember = 'member';
                $scope.initUserWorking = false;
            } else {
                $scope.initUserWorking = false;
            }
        });

        //if($scope.flower.company_product > 0  || $scope.flower.product_type == 2 || $scope.flower.product_type == 3 ){

        if ($rootScope.chosenCampaign) {
            if ($rootScope.chosenCampaign.special_type == 1) {
                $rootScope.userCampaigns = [];
                $rootScope.userCampaigns[0] = $rootScope.chosenCampaign;
                //$rootScope.chosenCampaign = [];
            }
            else {
                $rootScope.userCampaigns = [];
                $rootScope.chosenCampaign = [];
                $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

                if ($scope.flower.product_type == 2) {
                    $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 108);
                }
                else if ($scope.flower.product_type == 3) {
                    $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 118);
                }
                else {
                    $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 108);
                }
            }
        }
        else {
            $rootScope.userCampaigns = [];
            $rootScope.chosenCampaign = [];
            $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
            //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

            if ($scope.flower.product_type == 2) {
                $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 108);
            }
            if ($scope.flower.product_type == 3) {
                $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 118);
            }
            else {
                $scope.flower.newPrice = parseFloat($scope.flower.price / 100 * 108);
            }
        }
    }

    function initUser(isMember) {
        $scope.initUserWorking = true;

        purchaseModel.initUser(function (sender, user, receiver) {
            if (sender.id !== undefined) {
                $rootScope.sender = sender;
                $rootScope.loggedUser = jQuery.extend(true, {}, user);
                $scope.userContacts = $rootScope.sender.userContacts;
                $rootScope.userCampaigns = $rootScope.sender.userCampaigns;
                $rootScope.chosenCampaign = $rootScope.sender.chosenCampaign;
                $scope.chosenContact = $rootScope.sender.chosenContact;
                $rootScope.receiver = receiver;
                $scope.selectedContact = $scope.chosenContact;

                if ($rootScope.chosenCampaign) {
                    if ($rootScope.chosenCampaign.special_type == 1) {

                        if ($scope.flower.product_type == 2) {
                            //$scope.flower.price = parseFloat($scope.flower.price / 100 * 108);

                            if ($rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108) > 0) {
                                $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108);
                            }
                            else {
                                $rootScope.couponForCrossSell = 0;
                            }

                        }
                        else if ($scope.flower.product_type == 3) {
                            //$scope.flower.price = parseFloat($scope.flower.price / 100 * 108);

                            if ($rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 118) > 0) {
                                $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 118);
                            }
                            else {
                                $rootScope.couponForCrossSell = 0;
                            }

                        }
                        else {
                            //$scope.flower.price = parseFloat($scope.flower.price / 100 * 118);

                            if ($rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108) > 0) {
                                $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - parseFloat($scope.flower.price / 100 * 108);
                            }
                            else {
                                $rootScope.couponForCrossSell = 0;
                            }
                        }

                        /*if( $rootScope.chosenCampaign.value - $scope.flower.price > 0 ){
                         $rootScope.couponForCrossSell = $rootScope.chosenCampaign.value - $scope.flower.price;
                         }
                         else{
                         $rootScope.couponForCrossSell = 0;
                         }*/
                    }
                }

                if ($rootScope.chosenCrossProduct) {

                    if ($rootScope.couponForCrossSell > 0) {

                        if ($rootScope.chosenCrossProduct) {
                            $rootScope.crossSellPriceWithCoupon = ($rootScope.chosenCrossProduct.price / 100 * 108) - $rootScope.couponForCrossSell;
                            if ($rootScope.crossSellPriceWithCoupon < 0) {
                                $rootScope.crossSellPriceWithCoupon = 0.0;
                            }
                            $rootScope.chosenCrossProduct.crossSellPriceWithCoupon = $rootScope.crossSellPriceWithCoupon;
                            $rootScope.chosenCrossProduct.couponForCrossSell = $rootScope.couponForCrossSell;

                            $rootScope.chosenCrossProduct.newPrice = $rootScope.chosenCrossProduct.crossSellPriceWithCoupon;
                            $rootScope.chosenCrossProduct.allTotal = $rootScope.chosenCrossProduct.newPrice + $scope.flower.newPrice;
                            //$rootScope.chosenCrossProduct = $rootScope.chosenCrossProduct;
                        }
                    }
                    else {
                        $rootScope.chosenCrossProduct.newPrice = parseFloat($rootScope.chosenCrossProduct.price / 100 * 108);
                        $rootScope.chosenCrossProduct.allTotal = $rootScope.chosenCrossProduct.newPrice + $scope.flower.newPrice;
                        //$rootScope.chosenCrossProduct = $rootScope.chosenCrossProduct;
                        $rootScope.chosenCrossProduct.crossSellPriceWithCoupon = 0;
                        $rootScope.chosenCrossProduct.couponForCrossSell = 0;
                    }
                }

                isMember = 'member';
                $scope.initUserWorking = false;
                if ($scope.flower.company_product > 0 || $scope.flower.product_type == 2 || $scope.flower.product_type == 3) {

                    if ($rootScope.chosenCampaign) {
                        if ($rootScope.chosenCampaign.special_type == 1) {
                            $rootScope.userCampaigns = [];
                            $rootScope.userCampaigns[0] = $rootScope.chosenCampaign;
                            //$rootScope.chosenCampaign = [];
                        }
                        else {
                            $rootScope.userCampaigns = [];
                            $rootScope.chosenCampaign = [];

                            $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                            //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

                            if ($scope.flower.product_type == 2) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                            else if ($scope.flower.product_type == 3) {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                            }
                            else {
                                $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                            }
                        }
                    }
                    else {
                        $rootScope.userCampaigns = [];
                        $rootScope.chosenCampaign = [];

                        $scope.flower.priceWithDiscount = fixPrice($scope.flower.price);
                        //$scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);

                        if ($scope.flower.product_type == 2) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                        else if ($scope.flower.product_type == 3) {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 118);
                        }
                        else {
                            $scope.flower.newPrice = parseFloat($scope.flower.priceWithDiscount / 100 * 108);
                        }
                    }

                }
            } else {
                $scope.initUserWorking = false;
            }
        });

    }

    function sendGAEvent(checkoutSection) {
        var checkoutSectionText = '/checkout/' + checkoutSection;
        //analyticsHelper.addProduct($scope.flower.id, $scope.flower.name, fixPrice($scope.flower.price));
        if (checkoutSection == 'Cross-Sell') {
            analyticsHelper.sendCheckoutAction(3, isMember);
        }
        else if ($scope.section >= 3) {
            analyticsHelper.sendCheckoutAction(4, isMember);
        }
        else {
            analyticsHelper.sendCheckoutAction($scope.section, isMember);
        }
        analyticsHelper.sendPageView(checkoutSectionText);
    }

    $rootScope.$on('USER_CAMPAIGN_ADDED', function () {
        $rootScope.userCampaigns = $rootScope.loggedUser.campaigns.getCampaigns();
        $timeout(function () {
            $rootScope.userCampaigns = $rootScope.loggedUser.campaigns.getCampaigns();
        }, 1000);
    });

    $rootScope.$on('PROCESS_STATUS_CHANGED', function (event, args) {
        $scope.working = args;
    });

    $rootScope.$on('useCampaignAnother', function (event, args) {
        if(args){
            $scope.useCampaign(args);
        }
    });

    textareaHelper.checkMaxLength();

});

purchaseModule.controller('PurchaseNoteCtrl', function ($scope, $http, $timeout, $rootScope, $state, userAccount, translateHelper, otherExceptions, deviceDetector, errorMessages) {

    $scope.goToPurchase = function () {
        $scope.serverError = "";
        $scope.working = true;

        if ($scope.crossSellActive) {
            $scope.working = false;
            translateHelper.getText('CHECKOUT_URL_PAYMENT', function (paymentUrl) {
                $scope.incrementSectionProgress();
                $scope.isChecked = false;
                $state.go('purchaseProcess', {'purchaseStep': 'tat-kat'});
            });
        }
        else {
            sentPurchaseValues(function (response) {
                $scope.working = false;
                if (response) {
                    translateHelper.getText('CHECKOUT_URL_PAYMENT', function (paymentUrl) {
                        $scope.incrementSectionProgress();
                        $scope.isChecked = false;
                        $state.go('purchaseProcess', {'purchaseStep': paymentUrl});
                    });
                } else {
                    otherExceptions.sendException("note-purchase", "Kullanıcı Bilgilerini Gönderirken Hata Oldu");
                }
            });
        }
    };

    $scope.removeEmoji = function(){

        var ranges = [
            '\ud83c[\udf00-\udfff]', // U+1F300 to U+1F3FF
            '\ud83d[\udc00-\ude4f]', // U+1F400 to U+1F64F
            '\ud83d[\ude80-\udeff]' // U+1F680 to U+1F6FF
        ];

        //console.log(ranges);
        var str = $('#customer_note').val();

        //console.log(str);
        str = str.replace(new RegExp(ranges.join('|'), 'g'), '');
        console.log(new RegExp(ranges.join('|'), 'g'));
        console.log(str.replace(new RegExp(ranges.join('|'), 'g'), ''));
        $scope.note.card_message = str;
        console.log(str);

    };

    $scope.removeEmojiSender = function(){

        var ranges = [
            '\ud83c[\udf00-\udfff]', // U+1F300 to U+1F3FF
            '\ud83d[\udc00-\ude4f]', // U+1F400 to U+1F64F
            '\ud83d[\ude80-\udeff]' // U+1F680 to U+1F6FF
        ];

        var str = $scope.note.customer_sender_name;

        str = str.replace(new RegExp(ranges.join('|'), 'g'), '');
        $scope.note.customer_sender_name = str;

        console.log(str);
    };

    $scope.removeEmojiReceiver = function(){

        var ranges = [
            '\ud83c[\udf00-\udfff]', // U+1F300 to U+1F3FF
            '\ud83d[\udc00-\ude4f]', // U+1F400 to U+1F64F
            '\ud83d[\ude80-\udeff]' // U+1F680 to U+1F6FF
        ];

        var str = $scope.note.customer_receiver_name;

        str = str.replace(new RegExp(ranges.join('|'), 'g'), '');
        $scope.note.customer_receiver_name = str;
        console.log(str);


    };

    function sentPurchaseValues(callback) {
        var paymentInfo = initPurchaseValues();
        addSenderNote(paymentInfo);

        var postUrl = "/save-data-before-sale";
        if ($scope.isUserLogin()) {
            postUrl = "/user-save-data-before-sale";
        }

        $http.post(webServer + postUrl, paymentInfo)
            .success(function (response) {
                $rootScope.receiver.saleNumber = response.sale_number;
                callback(true);
            }).error(function (response) {
                errorMessages.getErrorMessage(response.description, function (errorMessage) {
                    $scope.serverError = errorMessage;
                    callback(false);
                });
            });
    }

    function initPurchaseValues() {
        if ($rootScope.receiver.time) {
            var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.start_hour;
            var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.end_hour;
        }
        else {
            var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].start_hour;
            var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.date.hours[0].end_hour;
        }
        //var sendingTimeStart = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.start_hour;
        var receiverDateStart = moment(sendingTimeStart, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');

        //var sendingTimeEnd = $rootScope.receiver.date.value + " " + $rootScope.receiver.time.end_hour;
        var receiveDateEnd = moment(sendingTimeEnd, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm:ss');

        var paymentInfo = {
            "product_id": $scope.flower.id,
            "product_name": $scope.flower.name,
            "web_site_id": 1,
            "city_id": $scope.flower.sendingDistrict.id,
            "city_name": $scope.flower.sendingDistrict.district,
            "mail": $rootScope.sender.email,
            "name": $rootScope.sender.name,
            "wanted_delivery_date": receiverDateStart,
            "wanted_delivery_date_end": receiveDateEnd,
            "contact_name": $rootScope.receiver.name,
            "contact_address": $rootScope.receiver.address,
            "contact_mobile": $rootScope.receiver.phoneNumber,
            "browser": deviceDetector.getBrowserInfo().browser + "/" + deviceDetector.getBrowserInfo().version,
            "device": deviceDetector.isMobile() ? "1/" + deviceDetector.getMobileInfo() : "0",
            "lang_id": translateHelper.getCurrentLang()
        };
        if ($rootScope.sender.id) {
            paymentInfo.access_token = $rootScope.sender.access_token;
        }

        if ($rootScope.receiver.contact_id) {
            paymentInfo.contact_id = $rootScope.receiver.contact_id;
        }

        return paymentInfo;
    }

    function addSenderNote(paymentInfo) {
        if ($rootScope.note.card_message !== undefined)
            paymentInfo.card_message = $rootScope.note.card_message;
        if ($rootScope.note.customer_sender_name !== undefined)
            paymentInfo.customer_sender_name = $rootScope.note.customer_sender_name;
        if ($rootScope.note.customer_receiver_name !== undefined)
            paymentInfo.customer_receiver_name = $rootScope.note.customer_receiver_name;
    }
});

purchaseModule.controller('ContactCtrl', function ($scope, $rootScope, $timeout, translateHelper) {
    $scope.setContactAdding = function () {
        if (isValuesEmpty()) {
            translateHelper.getText('CONTACT_MISSING_INFO_ERROR', function (errorMessage) {
                showErrorMessage(errorMessage);
            });
        }
        else if (isContactAlreadyAdded()) {
            translateHelper.getText('CONTACT_ALREADY_SAVED_ERROR', function (errorMessage) {
                showErrorMessage(errorMessage);
            });
        }
        else {
            $rootScope.isNewContact = true;
            $scope.isErrorHappened = false;
            translateHelper.getText('CONTACT_SAVED', function (contactSavedText) {
                $rootScope.newContactText = contactSavedText;
            });
        }

    };
    function isValuesEmpty() {
        if ($rootScope.receiver.name === undefined || $rootScope.receiver.address === undefined || $rootScope.receiver.phoneNumber === undefined) {
            return true;
        }
        return false;
    }

    function isContactAlreadyAdded() {
        return $rootScope.receiver.contact_id !== undefined;
    }

    function showErrorMessage(errorText) {
        $scope.isErrorHappened = true;
        $rootScope.newContactText = errorText;
        returnInitState();
    }

    function returnInitState() {
        $timeout(function () {
            if ($scope.isNewContact) {
                translateHelper.getText('CONTACT_SAVED', function (contactSavedText) {
                    $rootScope.newContactText = contactSavedText;
                });
            }
            else {
                translateHelper.getText('ADD_CONTACTS_TEXT', function (addContactText) {
                    $rootScope.newContactText = addContactText;
                });
            }
            $scope.isErrorHappened = false;
        }, 2000);
    }
});

purchaseModule.controller('PaymentCtrl', function ($scope, $rootScope, $document, $modal, translateHelper, errorMessages) {
    $scope.payment = {};

    $scope.tempStringTroy = '';

    $rootScope.tempStringTroyRoot = $scope.tempStringTroy;

    $scope.payment.selectedCoupon = $rootScope.chosenCampaign;

    $('input.contactCheckbox')
        .on('ifChecked', function (event) {
            $scope.payment.isContractReaded = true;
        })
        .on('ifUnchecked', function (event) {
            $scope.payment.isContractReaded = false;
        });

    //kissmetricsHelper.clickEvent('.purchaseCompleteButton', 'Ödeme yap butonuna bastı');

    translateHelper.getText('CVC_INFO', function (cvcInfo) {
        $scope.cvcInfo = "<p class='tooltipContext'>" + cvcInfo + "</p>";
    });

    $scope.openModel = function (contactType) {
        switch (contactType) {
            case 'longDistancePurchaseContract':
            {
                $modal.open({
                    templateUrl: 'views/contracts/longDistancePurchaseContract.html',
                    size: 'lg',
                    controller: 'ModalCtrl'
                });
                break;
            }
            case 'preinformForm':
            {
                $modal.open({
                    templateUrl: 'views/contracts/preinforming.html',
                    size: 'lg',
                    controller: 'ModalCtrl'
                });
                break;
            }
            case 'addCampaign':
            {
                if ($scope.flower.speciality == 1) {

                }
                else {
                    $modal.open({
                        templateUrl: '../../views/bf-utility-pages/addCampaignModal-v2.0.html',
                        size: 'sm',
                        controller: function ($scope, $rootScope, $timeout, $modalInstance) {
                            $scope.isChecked = false;
                            $scope.campaign = {};
                            $scope.errorMessage = "";

                            $scope.saveCampaign = function () {
                                if ($(addCampaignForm.campaignId).hasClass('ng-valid')) {

                                    $scope.isChecked = false;
                                    $rootScope.$emit("PROCESS_STATUS_CHANGED", true);
                                    $scope.errorMessage = "";
                                    $('#warningId').removeClass('hidden');
                                    $rootScope.loggedUser.campaigns.addCampaign($scope.campaign.id, $rootScope.loggedUser.access_token,
                                        function (result, errorCode) {
                                            if (result) {

                                                $timeout(function () {

                                                    $timeout(function () {
                                                        $rootScope.$emit("useCampaignAnother", errorCode.couponList[errorCode.couponList.length-1]);
                                                    }, 500);
                                                    //$scope.$parent.useCampaign(errorCode.couponList[0]);

                                                    $rootScope.$emit("USER_CAMPAIGN_ADDED");
                                                    $rootScope.$emit("PROCESS_STATUS_CHANGED", false);
                                                    $modalInstance.dismiss();
                                                    $('#warningId').addClass('hidden');
                                                }, 500);
                                            }
                                            else {
                                                errorMessages.getErrorMessage(errorCode, function(errorMessage){
                                                    $rootScope.$emit("PROCESS_STATUS_CHANGED", false);
                                                    $('#warningId').addClass('hidden');
                                                    $scope.errorMessage = errorMessage;
                                                });

                                            }
                                        });
                                } else {
                                    $scope.isChecked = true;
                                }
                            };
                        }
                    });
                }
                break;
            }
        }
    };

    $scope.isCampaignChosen = function () {
        return $rootScope.chosenCampaign !== undefined;
    };

    $scope.cardNumberChange = function (){

        if( $scope.payment.cardNumber ){

            if( $scope.payment.cardNumber.length > 6 ){

                var tempFirstString = $scope.payment.cardNumber.substring(0,7);

                if(
                tempFirstString == '9792-17' ||
                tempFirstString == '9792-80' ||
                tempFirstString == '9792-10' ||
                tempFirstString == '9792-12' ||
                tempFirstString == '9792-44' ||
                tempFirstString == '6500-52' ||
                tempFirstString == '6501-70' ||
                tempFirstString == '9792-09' ||
                tempFirstString == '9792-23' ||
                tempFirstString == '9792-06' ||
                tempFirstString == '9792-07' ||
                tempFirstString == '9792-08' ||
                tempFirstString == '9792-36' ||
                tempFirstString == '9792-04' ||
                tempFirstString == '6500-82' ||
                tempFirstString == '6500-92' ||
                tempFirstString == '6501-73' ||
                tempFirstString == '6504-56' ||
                tempFirstString == '6509-87' ||
                tempFirstString == '9792-33' ||
                tempFirstString == '6573-66' ||
                tempFirstString == '6579-98' ||
                tempFirstString == '6501-61' ||
                tempFirstString == '9792-15' ||
                tempFirstString == '9792-41' ||
                tempFirstString == '9792-42' ||
                tempFirstString == '9792-02' ||
                tempFirstString == '9792-03' ||
                tempFirstString == '3657-70' ||
                tempFirstString == '3657-71' ||
                tempFirstString == '3657-72' ||
                tempFirstString == '3657-73' ||
                tempFirstString == '6549-97' ||
                tempFirstString == '9792-40' ||
                tempFirstString == '9792-13' ||
                tempFirstString == '9792-27' ||
                tempFirstString == '9792-16' ||
                tempFirstString == '9792-18' ||
                tempFirstString == '9792-35' ||
                tempFirstString == '9792-48' ||
                tempFirstString == '9792-77' ||
                tempFirstString == '9792-54' ||
                tempFirstString == '9792-78' ||
                tempFirstString == '9792-49' ||
                tempFirstString == '9792-43' ||
                tempFirstString == '9792-50' ||
                tempFirstString == '9792-66' ||
                tempFirstString == '9792-60' ||
                tempFirstString == '9792-61' ||
                tempFirstString == '9792-62' ){

                    $rootScope.tempStringIsTroyCard = true;

                    if(  parseFloat( $scope.flower.newPrice )  < 100 && !$scope.chosenCrossProduct ){
                        $scope.tempStringTroy = '';
                    }
                    else{
                        $scope.tempStringTroy = ' - TROY indirimi 30 TL';
                        //console.log($scope.flower.newPrice);
                        $scope.troyPrice =  parseFloat( $scope.flower.newPrice  ) - 30;
                    }

                    //console.log(  parseFloat(parseFloat( $scope.flower.newPrice  ) - 30.00 ));

                }
                else{
                    $rootScope.tempStringIsTroyCard = false;
                    $scope.tempStringTroy = '';
                }
            }
            else{
                //console.log('out');
                $scope.tempStringTroy = '';
            }

        }
        else{

            //console.log('else');
            $scope.tempStringTroy = '';
        }

        $rootScope.tempStringTroyRoot = $scope.tempStringTroy;

    };

    String.prototype.toCardFormat = function () {
        return this.replace(/[^0-9]/g, "").substr(0, 16).split("").reduce(cardFormat, "");
        function cardFormat(str, l, i) {
            return str + ((!i || (i % 4)) ? "" : "-") + l;
        }
    };

    $document.ready(function () {
        $("#paymentCardNumber").keyup(function () {
            $(this).val($(this).val().toCardFormat());
        });
    });

    $scope.$watch('payment.cvcNumber', function (newValue) {
        if ($scope.payment.cardNumber !== undefined && $scope.payment.cvcNumber && $scope.payment.expirationMonth && $scope.payment.expirationYear) {
            //kissmetricsHelper.recordEvent('Kredi kartı bilgisi girdi');
            $scope.serverError = "";
        }
    });
});

purchaseModule.controller('ModalCtrl', function ($scope, $rootScope, purchaseFlowerModel, momentHelper) {
    $scope.date = momentHelper.getTime('DD-MM-YYYY');
    $scope.receiveDate = $rootScope.receiver.date.value;
    $scope.receiveTime = $rootScope.receiver.time.timeStep;
    $scope.flower = purchaseFlowerModel.getFlower();
    $scope.sender = $rootScope.sender;
    $scope.flowerPrice = $scope.flower.newPrice;
    $scope.saleNumber = $rootScope.receiver.saleNumber;
});

purchaseModule.directive('topSlider', function () {
    return {
        restrict: 'E',
        controller: function ($scope) {
            var showLimits = {
                min: 0,
                max: 3
            };

            $scope.canShow = function (index) {
                return (index >= showLimits.min && index <= showLimits.max);
            };

            $scope.move = function (direction, movingObj) {
                switch (direction) {
                    case 'left':
                    {
                        if (showLimits.min > 2) {
                            showLimits.min -= 3;
                            showLimits.max -= 3;
                        }
                        break;
                    }
                    case 'right':
                    {
                        if (showLimits.max < movingObj.length) {
                            showLimits.min += 3;
                            showLimits.max += 3;
                        }
                        break;
                    }
                }
            }
        }
    }
});
purchaseModule.directive('stopEvent', function () {
    return {
        restrict: 'A',
        link: function (scope, element, attr) {
            element.on(attr.stopEvent, function (e) {
                e.stopPropagation();
            });
        }
    };
});

function purchaseCheck() {
    var urlPath = window.location.pathname.split("/");
    var paymentPath = urlPath[2];

    if ($('#3Dcheck').find($('div')).hasClass('checked')) {
        $('#payment').attr('action', 'https://everybloom.com/submit-sale');  ////////http://188.166.86.116:3000/submit-sale
    }
    else {
        $('#payment').attr('action', 'https://everybloom.com/sales-without-secure'); /////http://188.166.86.116:3000/sales-without-secure
    }

    if ($('section.contracts').find('div.icheckbox_square-red').hasClass('checked')) {

        if (($(paymentForm.card_no).hasClass('ng-valid') && $(paymentForm.card_cvv).hasClass('ng-valid')
            && ($('.paymentCardExpirationDay').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationDay').find('.mobilDropdownSelect').hasClass('ng-valid-parse'))
            && $('.paymentCardExpirationYear').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationYear').find('.mobilDropdownSelect').hasClass('ng-valid-parse') ) || $('#checkIf').hasClass('ng-hide')) {

            if( $('#paymentCardNumber').val().length < 16 ){

                if (paymentPath == "odeme-bilgileri") {
                    $('label.contractsError').text('Kart Bilgileri Hatalı Veya Eksik');
                } else {
                    $('label.contractsError').text('Card Informations Are Wrong or Missing');
                }

                sendErrors('payment-purchase', 'eksik bilgi- kart no');
                return false;
            }


            $('#submitButton').removeAttr("onclick");
            $('#submitButton').removeAttr("type");
            $('#submitButton').addClass("disabled");
            $('#submitButton').attr("disabled", true);
            $('#submitButton').css("background-color", '#6F7376');
            $('#payment').submit();

            return true;
        } else {
            if (paymentPath == "odeme-bilgileri") {
                $('label.contractsError').text('Kart Bilgileri Hatalı Veya Eksik');
            } else {
                $('label.contractsError').text('Card Informations Are Wrong or Missing');
            }

            if (!($(paymentForm.card_no).hasClass('ng-valid')))
                sendErrors('payment-purchase', 'eksik bilgi- kart no');
            else if (!($(paymentForm.card_cvv).hasClass('ng-valid')))
                sendErrors('payment-purchase', 'eksik bilgi- cvc');
            else if ($('.paymentCardExpirationDay').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationDay').find('.mobilDropdownSelect').hasClass('ng-valid-parse') == false)
                sendErrors('payment-purchase', 'eksik bilgi- gün');
            else if ($('.paymentCardExpirationYear').find('.ui-select-container').hasClass('ng-valid-parse') || $('.paymentCardExpirationYear').find('.mobilDropdownSelect').hasClass('ng-valid-parse') == false)
                sendErrors('payment-purchase', 'eksik bilgi- ay');
            else
                sendErrors('payment-purchase', 'Kart Bilgileri Hatalı Veya Eksik');
            return false;
        }
    } else {
        if (paymentPath == "odeme-bilgileri") {
            $('label.contractsError').text('Kullanıcı Sözleşmesini Onaylaman Gerekiyor');
        } else {
            $('label.contractsError').text('Please Accept Contacts');
        }

        sendErrors('payment-purchase', 'Kullanıcı Sözleşmesini Onaylaman Gerekiyor');
        return false;
    }
}

function sendErrors(exceptionLocation, errorMessage) {
    var browserInfo =
        (function () {
            var ua = navigator.userAgent, tem,
                M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
            if (/trident/i.test(M[1])) {
                tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
                return 'IE ' + (tem[1] || '');
            }
            if (M[1] === 'Chrome') {
                tem = ua.match(/\b(OPR|Edge)\/(\d+)/);
                if (tem != null) return tem.slice(1).join(' ').replace('OPR', 'Opera');
            }
            M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];
            if ((tem = ua.match(/version\/(\d+)/i)) != null) M.splice(1, 1, tem[1]);
            return {'browser': M[0], 'version': M[1]};
        })();

    browserInfo.isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    $.ajax({
        type: "POST",
        url: webServer + "/logClient",
        contentType: "application/json",
        data: angular.toJson(
            {
                "method_name": errorMessage,
                "error_code": browserInfo.browser + "/" + browserInfo.version + "/" + browserInfo.isMobile,
                "error_message": exceptionLocation,
                "url": window.location.href
            })
    });
}