/**
 * Created by furkan on 24.03.2015.
 */

var userAccountModule = angular.module('userAccountModule', [
    'userPurchaseModule',
    'userBillingModule',
    'userContactModule',
    'userCampaignsModule',
    'ngCookies'
]);
userAccountModule.service('userAccount', function ($http,$rootScope, userPurchase,translateHelper, userBillingService, userContact, userCampaigns,userReminders, $cookies, $interval, otherExceptions) {
        $http.defaults.headers.post["Content-Type"] = "application/json";
        var client_id = ')EG0LZ2i9+pm.ox4+[SC2_K-S-E]@Z';
        var client_secret = '4m5ii2X>(#-17wqYbNZD_%}Azu2V';
        var user = undefined;

        var initBilling = function (callbackFunc) {
            userBillingService.initBillingInfo(user.access_token, function (result, errorMessage) {
                if (result) {
                    user.billingInfo = userBillingService;
                    callbackFunc(true);
                } else {
                    callbackFunc(false, errorMessage);
                }
            });
        };
        var initPurchases = function (callbackFunc) {
            userPurchase.initPurchases(user.access_token, function (result, errorMessage) {
                if (result) {
                    user.purchases = userPurchase;
                    callbackFunc(true);
                } else {
                    callbackFunc(false, errorMessage);
                }
            });
        };
        var initContacts = function (callbackFunc) {
            userContact.initContacts(user.access_token, function (result, errorMessage) {
                if (result) {
                    user.contacts = userContact;
                    callbackFunc(true);
                } else {
                    callbackFunc(false, errorMessage);
                }
            });
        };
        var initCampaings = function (callbackFunc) {
            userCampaigns.initCampaigns(user.access_token, function (result, errorMessage) {
                if (result) {
                    user.campaigns = userCampaigns;
                    callbackFunc(true);
                } else {
                    callbackFunc(false, errorMessage);
                }
            });
        };
        var initReminders = function(callbackFunc){
            userReminders.initReminders(user.access_token, function (result, errorMessage) {
                if (result) {
                    user.reminders = userReminders;
                    callbackFunc(true);
                } else {
                    callbackFunc(false, errorMessage);
                }
            });
        };
        var initUser = function (userData, callBackFunc) {
            setUserFromCookie(userData,true);
            callBackFunc(user);
        };

        function setUserFromCookie(userData,userNewLogged) {
            if (userData) {
                saveToCookies(userData);
            }
            if ($cookies.getObject('newUser') != undefined && $cookies.getObject('newUser') != "undefined") {
                user = $cookies.getObject('newUser');
                user.isloaded = true;
                //_kmq.push(['identify', user.email ]);
                if(userNewLogged){
                    $http.post(webServer+ '/log-user-login', { 'username': user.email }).success(function(response){
                        user.name = response.name;
                        user.mobile = response.mobile;
                        user.email = response.email;
                    })
                        .error(function () {
                            otherExceptions.sendException("logUserLogin", "Kullanıcı Son Login Tarihi Gönderilemedi");
                        });
                }
            }
        }

        function saveToCookies(userData){
            var now = new Date();
            var exp = new Date(now.getFullYear() + 1, now.getMonth(), now.getDate());
            $cookies.putObject('newUser', userData, {
                expires : exp
            });
        }

        var getUserDatas = function (callBackFunc) {
            var isSucceeded = true;
            user.isloaded = false;

            initPurchases(function (result, errorMessage) {
                isSucceeded = result;
                initBilling(function (result, errorMessage) {
                    isSucceeded = result && isSucceeded;
                    initContacts(function (result, errorMessage) {
                        isSucceeded = result && isSucceeded;
                        initCampaings(function (result, errorMessage) {
                            isSucceeded = result && isSucceeded;
                            initReminders(function (result, errorMessage) {
                                user.isloaded = true;
                                callBackFunc(result && isSucceeded, errorMessage);
                            });
                        });
                    });
                });
            });
        };

    return {
        updateUser: function ( saleDetail ,userMail, userName, userPhoneNumber, access_token,user_id,user_fb_id, callbackFunction) {
            var tempUserInput = {};
            tempUserInput.access_token = user.access_token;
            tempUserInput.id = user.id;
            tempUserInput.email = userMail;
            userPhoneNumber = userPhoneNumber.replace('(' , '').replace(')' , '').replace('-' , '').replace('-' , '').replace('-' , '');
            tempUserInput.mobile = userPhoneNumber;
            tempUserInput.name = userName;
            tempUserInput.saleDetail = saleDetail;
            $http.post(webServer+ '/user-update-user-info', tempUserInput)
                .success(function (data)
                {
                    user = {
                        email : userMail,
                        name: userName,
                        mobile: userPhoneNumber,
                        access_token: access_token,
                        id: user_id,
                        fb_id: user_fb_id,
                        sale_info: saleDetail
                    };

                    setUserFromCookie(user,false);
                    getUserDatas(function(){
                        callbackFunction(true);
                    });
                })
                .error(function (data)
                {
                    callbackFunction(false, data.description);
                });
        },
        updateUserPersonalBilling: function (userBillingTC, userBillingDistrict, userBillingCity, userBillingAddress, callbackFunc) {
            user.billingInfo.updateBillingInfo("1", user.access_token, userBillingAddress, callbackFunc, userBillingDistrict, userBillingCity,userBillingTC);
        },
        updateUserCorporateBilling: function (userBillingAddress, userBillingCompany, userBillingTaxOffice, userBillingTaxNo, callbackFunc) {
            user.billingInfo.updateBillingInfo("2", user.access_token, userBillingAddress, callbackFunc, userBillingCompany, userBillingTaxOffice, userBillingTaxNo);
        },
        changePassword: function (currentPassword, newPassword, callbackFunc) {
            var userInfo = {
                access_token: user.access_token,
                new_password : newPassword,
                old_password : currentPassword
            };
            $http.post(webServer + '/user-change-password', userInfo)
                .success(function (data) {
                    callbackFunc(true);
                })
                .error(function (data, status, headers, config) {
                    callbackFunc(false, data.description);
                });
        },
        passwordRetrieval: function(userMail,callbackFunc){
            var userInfo = {
                "email": userMail
            };
            $http.post(webServer + '/changePassword', userInfo)
                .success(function (data) {
                    callbackFunc(true);
                })
                .error(function (data) {
                    callbackFunc(false, data.description);
                });
        },
        rescuePassword: function(userId, userToken, newPassword, callbackFunc){
            var userInfo = {
                user_id : userId,
                "password" : newPassword,
                "token" : userToken
            };

            $http.post(webServer + "/login-with-new-password", userInfo)
                .success(function (data) {
                    initUser(data, function (result) {
                        callbackFunc(result);
                    });
                })
                .error(function (data) {
                    callbackFunc(false, data.description);
                });
        },
        signIn: function (userMail, userPassword, callbackFunc) {
            var userInfo = {
                username: userMail,
                password: userPassword,
                grant_type: 'password',
                client_id: client_id,
                client_secret: client_secret
            };

            $http.post(webServer + "/user-authLogin", userInfo)
                .success(function (data) {
                    initUser(data, function (result) {
                        callbackFunc(result);
                    });
                    user.company_user = data.company_user;
                    user.company_name = data.company_name;
                })
                .error(function (data, status, headers, config) {
                    callbackFunc(false, data.description);
                });
        },
        afftrckRegister: function(id){
            $('<iframe src="https://ad.afftrck.com/SLE1?adv_sub=' + id + '" frameborder="0" scrolling="no" id="myFrame"></iframe>').appendTo('body');
        },
        signUp: function (email, name, password, newsLetterSubscription, callbackFunc) {
            var userInfo = {
                username: email,
                name: name,
                password: password,
                newsLetter: newsLetterSubscription,
                grant_type: 'password',
                client_id: client_id,
                client_secret: client_secret,
                lang_id : translateHelper.getCurrentLang()
            };

            $http.post(webServer + '/oauth/register_token', userInfo)
                .success(function (data) {
                    userInfo.password = "";
                    userInfo.id = data.userId;
                    userInfo.email = userInfo.username ;
                    userInfo.mobile = "";
                    userInfo.access_token = data.access_token;
                    userInfo.client_id = "";
                    userInfo.client_secret = "";
                    userInfo.grant_type = "";
                    initUser(userInfo, function (result, errorMessage) {
                        callbackFunc(result, errorMessage);
                    });
                    user.company_user = data.company_user;
                    user.company_name = data.company_name;
                })
                .error(function (data) {
                    callbackFunc(false, data.description);
                });
        },
        facebookLogin: function ( id , email, name, lastName , gender , callbackFunc) {
            var userInfo = {
                fb_id : id,
                username: email,
                name: name,
                surname : lastName,
                gender : gender,
                grant_type: 'password',
                client_id: client_id,
                client_secret: client_secret,
                password : 'dunyaninenzorf2#$%^&**!gsdfgs23477faghqajk[',
                lang_id : translateHelper.getCurrentLang()
            };

            $http.post( webServer +'/facebookLogin', userInfo)
                .success(function (data) {
                    initUser(data, function (result, errorMessage) {
                        callbackFunc(result, errorMessage);
                    });
                })
                .error(function (data) {
                    callbackFunc(false, data.description);
                });
        },
        isFBRegister: function(){
            return !user.firstLogin;
        },
        logOut: function () {
            $cookies.remove('newUser');
            user = undefined;
        },
        getUser: function (callback,isUserDatasNeed) {
            if (user === undefined) {
                initUser(undefined,function(result,errorMessage){
                    if(result){
                        if(isUserDatasNeed)
                            getUserDatas(function(){
                                callback(user);
                            });
                        else
                            callback(user);
                    }
                    else
                        callback(undefined);
                });
            } else {
                if(!user.isloaded){
                    var checkIsLoaded = $interval(function() {
                        if(user.isloaded){
                            callback(user);
                            isLoaded();
                        }
                    }, 1000);

                    function isLoaded(){
                        $interval.cancel(checkIsLoaded);
                    }
                }else{
                    if(isUserDatasNeed && user.purchases === undefined){
                        getUserDatas(function(){
                            callback(user);
                        });
                    }else{
                        callback(user);
                    }
                }
            }
        },
        checkUserLoggedin : function(){
            return user !== undefined;
        },
        checkUserExit : function(){
            var result = $cookies.getObject('newUser') !== undefined ? true : false;

            if(!result)
                user = undefined;

            return result;
        },
        getPurchases: function () {
            return user.purchases.getPurchases();
        },
        getBillingInfo: function () {
            return user.billingInfo.getBillingInfo();
        },
        getContacts: function () {
            return user.contacts.getContacts();
        },
        initUser: function (value, callBackFunc) {
            if (user === undefined) {
                initUser(value, callBackFunc);
            } else {
                callBackFunc(true);
            }
        },
        updateUserData: function(callBackFunc){
            getUserDatas(function(){
                callBackFunc(user);
            })
        },
        setPhoneNumber: function(newMobile){
            if( user.mobile === undefined || user.mobile === "" ){
                user.mobile = newMobile;
            }
        },
        getPhoneNumber : function(){
            return user !== undefined ? user.mobile : undefined;
        },
        getUserMail : function(){
            return user !== undefined ? user.email : undefined;
        }

    };
});