'use strict';

/**
 * @ngdoc service
 * @name app.purchaseUserModel
 * @description
 * # purchaseModel
 * Service in the bloomNFresh.
 */
angular.module('app')
    .service('purchaseModel', function ($http, $cookies, userAccount, purchaseFlowerModel) {
        var receiver = {};
        var sender = {};
        var note = {};
        var userInfos = {};

        this.setCouponsWithDate = function( flowerId , startDate){
            if(userInfos.campaigns){
                return userInfos.campaigns.getSuitableCampaignsDate(flowerId, startDate);
            }
        };

        this.setCouponsWithOnlyBulgari = function( flower ){
            if(userInfos.campaigns){
                var tempCoupons = userInfos.campaigns.getBulgariCouponOnly(flower.id , sender.access_token);
                initCoupons(flower);
                return tempCoupons;
            }
        };

        function calculateTheLowestPriceFromCoupons(flowerPrice, flower) {
            var lowestPrice = flowerPrice;
            var price = flowerPrice;

            sender.userCampaigns.forEach(function (userCampaign) {
                var discountedPrice = calculatePriceFromCoupon(flowerPrice, userCampaign.type, userCampaign.value, userCampaign.special_type, flower );

                if (discountedPrice < lowestPrice) {
                    lowestPrice = discountedPrice;
                    if (sender.chosenCampaign !== undefined)
                        sender.chosenCampaign.isActive = false;
                    userCampaign.isActive = true;
                    sender.chosenCampaign = userCampaign;

                    price = lowestPrice;
                }
                else
                    userCampaign.isActive = false;
            });

            return price;
        }

        function calculatePriceFromCoupon(flowerPrice, couponType, couponValue, special, flower) {
            var price = 0.0;
            //console.log(couponType);
            switch (couponType) {
                case "1":
                {

                    if( special == 1 ){
                        price = fixPrice(flower.price) - parseInt(couponValue);

                        if( flower.product_type == 2 ){
                            //$scope.flower.price = parseFloat($scope.flower.price / 100 * 108);

                            price = fixPrice(parseFloat(flower.price / 100 * 108)) - parseInt(couponValue);

                        }
                        else{
                            //$scope.flower.price = parseFloat($scope.flower.price / 100 * 118);

                            price = fixPrice(parseFloat(flower.price / 100 * 118)) - parseInt(couponValue);
                        }

                    }
                    else{
                        price = fixPrice(flower.price) - parseInt(couponValue);
                    }

                    //price = fixPrice(flowerPrice) - parseInt(couponValue);
                    if(price <= 0)
                        price = 0;
                    break;
                }
                case "2":
                {
                    var fixedFlowerPrice = fixPrice(flowerPrice);
                    price = fixedFlowerPrice - (fixedFlowerPrice / 100 * parseInt(couponValue));
                    break;
                }
                default :
                {
                    price = fixPrice(flowerPrice);
                }
            }

            return price;
        }

        function fixPrice(price) {
            var ret = replaceString(price, ",", ".");
            return parseFloat(ret);
        }

        function initCoupons(flower) {
            sender.userCampaigns = userInfos.campaigns.getSuitableCampaigns(flower.id);

            var price = flower.price;

            if ($cookies.getObject('usedCoupon')) {
                flower.campaign = $cookies.getObject('usedCoupon');
                $cookies.remove('usedCoupon');
            } else {
                if (flower.campaign === undefined)
                    price = calculateTheLowestPriceFromCoupons(price, flower);
            }

            if (sender.chosenCampaign !== undefined)
                purchaseFlowerModel.setDiscountedPrice(price);

            if (flower.campaign !== undefined) {
                sender.userCampaigns.forEach(function (campaign) {
                    if (campaign.id === flower.campaign.id) {
                        if (sender.chosenCampaign !== undefined)
                            sender.chosenCampaign.isActive = false;

                        sender.chosenCampaign = campaign;
                        campaign.isActive = true;

                        price = calculatePriceFromCoupon(flower.price, campaign.type, campaign.value, campaign.special_type, flower);
                        purchaseFlowerModel.setDiscountedPrice(price);
                    }
                    else {
                        campaign.isActive = false;
                    }
                });
                flower.campaign = undefined;
            }
        }

        function initCouponsDate(flower , date) {
            sender.userCampaigns = userInfos.campaigns.getSuitableCampaignsDate(flower.id , date);
            var tempUserCampaigns = [];
            if( flower.product_type != 1 ){
                sender.userCampaigns.forEach(function (campaign) {
                    if( campaign.special_type == 1 ){
                        tempUserCampaigns[0] = campaign;
                    }
                });
                sender.userCampaigns = tempUserCampaigns;
            }

            var price = flower.price;

            if ($cookies.getObject('usedCoupon')) {
                flower.campaign = $cookies.getObject('usedCoupon');
                $cookies.remove('usedCoupon');
            } else {
                if (flower.campaign === undefined)
                    price = calculateTheLowestPriceFromCoupons(price, flower);
            }

            if (sender.chosenCampaign !== undefined)
                purchaseFlowerModel.setDiscountedPrice(price);

            if (flower.campaign !== undefined) {
                sender.userCampaigns.forEach(function (campaign) {
                    if (campaign.id === flower.campaign.id) {
                        if (sender.chosenCampaign !== undefined)
                            sender.chosenCampaign.isActive = false;

                        sender.chosenCampaign = campaign;
                        campaign.isActive = true;

                        price = calculatePriceFromCoupon(flower.price, campaign.type, campaign.value, campaign.special_type, flower);
                        purchaseFlowerModel.setDiscountedPrice(price);
                    } else {
                        campaign.isActive = false;
                    }
                });

                flower.campaign = undefined;
            }
        }

        function initContacts(flower) {
            sender.userContacts = userInfos.contacts.getContacts();
            sender.userContacts.forEach(function (userContact) {
                userContact.isActive = false;
            });

            if ((flower !== undefined && flower.contact !== undefined) || (receiver !== undefined && receiver.contact_id !== undefined)) {
                var contact_id = receiver.contact_id !== undefined ? receiver.contact_id : flower.contact.id;
                sender.userContacts.forEach(function (userContact) {
                    if (userContact.id === contact_id) {
                        var sendingDistrict = flower.sendingDistrict;

                        if (sendingDistrict.district === userContact.district) {
                            sender.chosenContact = userContact;

                            if (receiver.contact_id === undefined){
                                receiver.name =  userContact.name;
                                receiver.lastName =  userContact.surname;
                                receiver.phoneNumber =  parseInt(userContact.mobile);
                                receiver.address =  userContact.address;
                                receiver.contact_id =  userContact.id;
                            }

                            userContact.isActive = true;
                        }
                        //else {
                        //    receiver = {};
                        //}

                    }
                });
            }
        }

        this.initUser = function (callback) {

            userAccount.getUser(function (user) {

                if (user) {
                    userInfos = jQuery.extend(true, {}, user);
                    var flower = purchaseFlowerModel.getFlower();

                    sender = {
                        id: userInfos.id,
                        email: userInfos.email,
                        mobile: userInfos.mobile,
                        name: userInfos.name,
                        access_token: userInfos.access_token
                    };
                    initCoupons(flower);
                    initContacts(flower);
                }
                callback(sender, user, receiver);
            }, true);
        };

        this.initUserDate = function ( dateTemp , callback) {

            userAccount.getUser(function (user) {

                if (user) {
                    userInfos = jQuery.extend(true, {}, user);
                    var flower = purchaseFlowerModel.getFlower();

                    sender = {
                        id: userInfos.id,
                        email: userInfos.email,
                        mobile: userInfos.mobile,
                        name: userInfos.name,
                        access_token: userInfos.access_token
                    };
                    initCouponsDate(flower , dateTemp );
                    initContacts(flower);
                }
                callback(sender, user, receiver);
            }, true);
        };

        this.setReceiver = function (name, mobile, address, saleNumber, cardMessage) {
            receiver = {
                name: name,
                phoneNumber: mobile,
                address: address,
                saleNumber: saleNumber,
                card_message: cardMessage
            };

            return receiver;
        };

        this.setNote = function (noteReceiver, noteSender, noteMessage) {
            note = {
                customer_receiver_name: noteReceiver,
                customer_sender_name: noteSender,
                card_message: noteMessage
            };

            return note;
        };

        this.setSender = function (name, mobile, mail) {
            if (sender === undefined)
                sender = {};

            sender.name = name;
            sender.mobile = mobile;
            sender.email = mail;

            return sender;
        };

        this.getReceiver = function () {
            return receiver;
        };
        this.getNote = function () {
            return note;
        };
        this.getSender = function () {
            return sender;
        };

        this.getChosenCampaign = function () {
            return sender.chosenCampaign;
        };

        this.getChosenContact = function () {
            return sender.chosenContact;
        };

        this.addContact = function (district) {
            userInfos.contacts.addContact(district.id, district.district, receiver.name, receiver.address,
                receiver.phoneNumber, sender.access_token,
                function (response, result) {
                    if (response)
                        receiver.contact_id = result;
                });
        };

        this.sendUserPassedReceiverDatas = function (receiverDateStart, sendingDistrict, flowerName, callback) {

            var senderDatas = {
                access_token: sender.access_token,
                name: receiver.name,
                phone: receiver.phoneNumber,
                city: sendingDistrict,
                address: receiver.address,
                delivery_date: receiverDateStart,
                product_name: flowerName
            };

            $http.post(webServer + "/user-log-receiver", senderDatas)
                .success(function (response) {
                    callback(true);
                })
                .error(function (response) {
                    callback(false);
                });
        };

        this.checkCrossSell = function ( callback) {

            var senderDatas = {
                access_token: sender.access_token
            };

            if($cookies){
                var tempSelectedCity = $cookies.getObject('selectCity');
            }
            else{
                tempSelectedCity = {
                    value : 'ist'
                }
            }

            $http.get(webServer + "/get-active-crossSellProducts/" + tempSelectedCity.value )
                .success(function (response) {
                    callback(response);
                })
                .error(function (response) {
                    callback(response);
                });
        };

        this.getSubsFreq = function ( callback) {

            var senderDatas = {
                access_token: sender.access_token
            };

            $http.get(webServer + "/get-active-freq")
                .success(function (response) {
                    callback(response);
                })
                .error(function (response) {
                    callback(response);
                });
        };

        this.getSubsFirstDays = function ( callback) {

            var senderDatas = {
                access_token: sender.access_token
            };

            $http.get(webServer + "/get-active-subs-firstDays")
                .success(function (response) {
                    callback(response);
                })
                .error(function (response) {
                    callback(response);
                });
        };

        this.getSubsHours = function ( callback) {

            var senderDatas = {
                access_token: sender.access_token
            };

            $http.get(webServer + "/get-active-subs-hours")
                .success(function (response) {
                    callback(response);
                })
                .error(function (response) {
                    callback(response);
                });
        };

        this.getProducts= function ( callback) {

            var senderDatas = {
                access_token: sender.access_token
            };

            $http.get(webServer + "/get-active-products")
                .success(function (response) {
                    callback(response);
                })
                .error(function (response) {
                    callback(response);
                });
        };

    });
