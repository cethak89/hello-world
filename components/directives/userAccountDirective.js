'use strict';

/**
 * @ngdoc directive
 * @name bloomNFresh.directive:userAccountDirective
 * @description
 * # userAccountDirective
 */
angular.module('menuModule')
    .directive('userAccount', function (otherExceptions) {
        return {
            restrict: 'E',
            controller: function ($scope, $rootScope, $timeout, userAccount, errorMessages, $http) {
                $scope.isChecked = false;
                $scope.isAccountFormOpen = true;
                $scope.isPasswordFormOpen = true;
                $scope.isBillingFormOpen = true;
                $scope.updateUserWorking = false;
                $scope.userInfo = jQuery.extend(true, {}, $scope.loggedUser);

                $scope.saleInfoStatus = 0;

                $http.get(webServer + '/getSaleShowInfo/' + $scope.loggedUser.id )
                    .success(function (result) {
                        $scope.saleInfoStatus = result[0];
                    });

                $scope.updateUser = function () {
                    if ($(userAccountForm.loggedUserMail).hasClass('ng-valid') && $(userAccountForm.loggedUserName).hasClass('ng-valid') && $(userAccountForm.loggedUserPhone).hasClass('ng-valid')) {
                        $scope.isChecked = false;
                        var user = $scope.userInfo;
                        $scope.updateUserWorking = true;
                        userAccount.updateUser( $scope.saleInfoStatus ,$(userAccountForm.loggedUserMail).val(), $(userAccountForm.loggedUserName).val(), $(userAccountForm.loggedUserPhone).val(), user.access_token, user.id, user.fb_id,
                            function (result, errorCode) {
                                if (result) {
                                    $scope.processSuccess = true;
                                    $scope.updateUserWorking = false;

                                    $timeout(function () {
                                        $scope.processSuccess = false;
                                        $scope.switchSection(0);
                                        $rootScope.$broadcast('UPDATE_USER');
                                    }, 1000);
                                }
                                else {
                                    errorMessages.getErrorMessage(errorCode, function (errorMessage) {
                                        $scope.errorMessage = errorMessage;
                                        $scope.updateUserWorking = false;
                                    });
                                }
                            });
                    } else {
                        if (!($(userAccountForm.loggedUserMail).hasClass('ng-valid')))
                            otherExceptions.sendException("update user-profil", "Eksik Bilgi- mail adresi");
                        else if (!($(userAccountForm.loggedUserName).hasClass('ng-valid')))
                            otherExceptions.sendException("update user-profil", "Eksik Bilgi- ismi");
                        else if (!($(userAccountForm.loggedUserPhone).hasClass('ng-valid')))
                            otherExceptions.sendException("update user-profil", "Eksik Bilgi- numarası");
                        else
                            otherExceptions.sendException("update user-profil", "Eksik Bilgi");

                        $scope.isChecked = true;
                    }
                };
                $scope.switchSection = function (section) {
                    switch (section) {
                        case 0:
                        {
                            $scope.isAccountFormOpen = !$scope.isAccountFormOpen;
                            $scope.isPasswordFormOpen = true;
                            $scope.isBillingFormOpen = true;

                            $('.saleDetailPopUp input').iCheck({
                                checkboxClass : 'icheckbox_square-red',
                                radioClass : 'iradio_flat-red'
                            });

                            $('input.newsletterCheckbox')
                                .on('ifChecked', function (event) {
                                    $scope.userInfo.sale_info = 0;
                                    $scope.saleInfoStatus = 0;

                                })
                                .on('ifUnchecked', function (event) {
                                    $scope.userInfo.sale_info = 1;
                                    $scope.saleInfoStatus = 1;
                                });

                            break;
                        }
                        case 1:
                        {
                            $scope.isAccountFormOpen = true;
                            $scope.isPasswordFormOpen = !$scope.isPasswordFormOpen;
                            $scope.isBillingFormOpen = true;
                            break;
                        }
                        case 2:
                        {
                            $scope.isAccountFormOpen = true;
                            $scope.isPasswordFormOpen = true;
                            $scope.isBillingFormOpen = !$scope.isBillingFormOpen;
                            break;
                        }
                    }
                }
            },
            templateUrl: '../../views/menu-views/userAccountsTab-v2.0.html'
        };
    })
    .controller('PasswordChangeCtrl', function ($scope, $timeout, userAccount, otherExceptions, errorMessages) {
        $scope.isChecked = false;
        $scope.errorMessage = "";
        $scope.passwordChange = function () {
            if ($(passwordChangeForm.currentPassword).hasClass('ng-valid') && $(passwordChangeForm.newPassword).hasClass('ng-valid')
                && $(passwordChangeForm.newPasswordCheck).hasClass('ng-valid')) {
                userAccount.changePassword($scope.userPasswordModel.currentPassword, $scope.userPasswordModel.newPassword,
                    function (result, errorCode) {
                        if (result) {
                            $scope.processSuccess = true;
                            $timeout(function () {
                                $scope.switchSection(1);
                                $scope.userPasswordModel = {};
                                $scope.processSuccess = false;
                                resetForm($scope.passwordChangeForm);
                            }, 1000);
                        } else {
                            errorMessages.getErrorMessage(errorCode, function (errorMessage) {
                                $scope.processSuccess = false;
                                $scope.isChecked = true;
                                $scope.errorMessage = errorMessage;
                            });
                        }
                    });
            }
            else {
                errorMessages.getErrorMessage("MISSING_INFO", function (errorMessage) {
                    $scope.isChecked = true;
                    if (!($(passwordChangeForm.currentPassword).hasClass('ng-valid')))
                        otherExceptions.sendException("Password Change", "Eksik Bilgi- şimdiki şifresi");
                    else if (!($(passwordChangeForm.newPassword).hasClass('ng-valid')))
                        otherExceptions.sendException("Password Change", "Eksik Bilgi- yeni şifre");
                    else if (!($(passwordChangeForm.newPasswordCheck).hasClass('ng-valid')))
                        otherExceptions.sendException("Password Change", "Eksik Bilgi- yeni şifre tekrar");
                    else
                        otherExceptions.sendException("Password Change", "Eksik Bilgi");

                    $scope.errorMessage = errorMessage;
                });
            }
        };

        function resetForm(form) {
            form.$setPristine();
            form.$setUntouched();
            $scope.isChecked = false;
            $scope.errorMessage = "";
        }
    });
