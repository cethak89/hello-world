'use strict';

/**
 * @ngdoc service
 * @name app.reminderHelper
 * @description
 * # reminderHelper
 * Service in the bloomNFresh.
 */
angular.module('app')
  .service('reminderHelper', function (userAccount,$modal,$http) {
        this.addReminder = function(flowerName,flowerid, callback){
            if(userAccount.checkUserLoggedin()){
                var userMail = userAccount.getUserMail();
                submitReminderMail(userMail,flowerid, function(result){
                    callback(result);
                });
            }else{
                $modal.open({
                    templateUrl: '../../views/bf-utility-pages/remindMeLaterModal-v2.0.1.html',
                    size: 'sm',
                    controller: function ($scope, $timeout, errorMessages, $modalInstance, otherExceptions) {
                        $scope.isChecked = false;
                        $scope.isSuccess = false;
                        $scope.reminder = {};
                        $scope.errorMessage = "";
                        $scope.reminder.flower_name = flowerName;

                        $scope.saveReminder = function () {
                            if ($(remindMeForm.reminderMail).hasClass('ng-valid')) {
                                $scope.isChecked = false;
                                submitReminderMail($scope.reminder.mail,flowerid, function (result, errorCode) {
                                    if (result) {
                                        $scope.isSuccess = true;

                                        $timeout(function () {
                                            $modalInstance.dismiss();
                                            callback(result);
                                        }, 2000);
                                    }
                                    else {
                                        errorMessages.getErrorMessage(errorCode, function (errorMessage) {
                                            $scope.isChecked = true;
                                            $scope.errorMessage = errorMessage;
                                        });
                                    }
                                });
                            } else {
                                $scope.isChecked = true;
                                otherExceptions.sendException("remind me later",  "Eksik Bilgi");
                            }
                        };
                    }
                });
            }
        };

        function submitReminderMail(mail,flowerId,callback){
            var data =
            {
                "product_id" : flowerId,
                "mail" : mail
            };
            $http.post(webServer + '/add-product-later-mail', data)
                .success(function () {
                    callback(true);
                }).error(function (result) {
                    callback(false, result.description);
                });
        }
  });
