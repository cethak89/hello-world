'use strict';

/**
 * @ngdoc directive
 * @name menuModule.directive:userReminderDirective
 * @description
 * # userReminderDirective
 */
angular.module('menuModule')
    .directive('userReminder', function () {
        return {
            restrict: 'E',
            controller: function ($scope, translateHelper) {
                $scope.working = false;

                $scope.isRemindersExist = function () {
                    if ($scope.reminders)
                        return $scope.reminders.length > 0;
                    else
                        return false;
                };

                $scope.getReminderDate = function (reminder) {
                    var momentObj = moment().locale(translateHelper.getCurrentLang());
                    momentObj.date(reminder.reminder_day);
                    var month = parseInt(reminder.reminder_month) - 1;
                    momentObj.month(month);
                    return momentObj.format("DD MMMM");
                };

                $scope.removeReminder = function (reminder) {
                    if (reminder.id !== undefined) {
                        $scope.working = true;
                        $scope.loggedUser.reminders.removeReminder(reminder.id, $scope.loggedUser.access_token,
                            function (result) {
                                if (result) {
                                    $scope.isChecked = false;
                                    $scope.updateUserInfo();
                                    $scope.working = false;
                                } else {
                                    $scope.isChecked = true;
                                    $scope.errorMessage = arguments[1];
                                    $scope.working = false;
                                }
                            });
                    }
                };
            },
            templateUrl: '../../views/menu-views/userRemindersTab-v2.0.html'
        };
    })
    .controller('AddReminderCtrl',function($scope,$timeout,otherExceptions,momentHelper,translateHelper){
        $scope.errorMessage = "";
        $scope.isChecked = false;
        $scope.isEditContact = false;
        $scope.working = false;
        $scope.reminder = {};
        $scope.processSuccess = false;

        $scope.today = function() {
            $scope.reminder.date = new Date();
        };

        $scope.open = function($event) {
            $event.preventDefault();
            $event.stopPropagation();

            $scope.opened = true;
        };

        $scope.dateOptions = {
            formatYear: 'yy',
            startingDay: 1,
            showWeeks : false
        };

        $scope.months = momentHelper.returnMonths();/*[
            {
                display: "Ocak",
                value: 1
            },
            {
                display: "Şubat",
                value: 2
            },
            {
                display: "Mart",
                value: 3
            }, {
                display: "Nisan",
                value: 4
            }, {
                display: "Mayıs",
                value: 5
            }, {
                display: "Haziran",
                value: 6
            }, {
                display: "Temmuz",
                value: 7
            }, {
                display: "Ağustos",
                value: 8
            }, {
                display: "Eylül",
                value: 9
            }, {
                display: "Ekim",
                value: 10
            }, {
                display: "Kasım",
                value: 11
            }, {
                display: "Aralık",
                value: 12
            }
        ];*/
        $scope.days = [
            {
                display: "01",
                value: 1
            }, {
                display: "02",
                value: 2
            }, {
                display: "03",
                value: 3
            }, {
                display: "04",
                value: 4
            }, {
                display: "05",
                value: 5
            }, {
                display: "06",
                value: 6
            }, {
                display: "07",
                value: 7
            }, {
                display: "08",
                value: 8
            }, {
                display: "09",
                value: 9
            }, {
                display: "10",
                value: 10
            }, {
                display: "11",
                value: 11
            }, {
                display: "12",
                value: 12
            },{
                display: "13",
                value: 13
            }, {
                display: "14",
                value: 14
            }, {
                display: "15",
                value: 15
            }, {
                display: "16",
                value: 16
            }, {
                display: "17",
                value: 17
            }, {
                display: "18",
                value: 18
            }, {
                display: "19",
                value: 19
            }, {
                display: "20",
                value: 20
            }, {
                display: "21",
                value: 21
            }, {
                display: "22",
                value: 22
            }, {
                display: "23",
                value: 23
            }, {
                display: "24",
                value: 24
            }, {
                display: "25",
                value: 25
            }, {
                display: "26",
                value: 26
            }, {
                display: "27",
                value: 27
            }, {
                display: "28",
                value: 28
            }, {
                display: "29",
                value: 29
            }, {
                display: "30",
                value: 30
            }, {
                display: "31",
                value: 31
            }
        ];

        $scope.toggleMin = function() {
            $scope.minDate = $scope.minDate ? null : new Date();
        };
        $scope.toggleMin();

        $scope.clear = function () {
            $scope.reminder.date = null;
        };

        $scope.format = 'dd-MMMM-yyyy';

        $scope.addReminder = function () {
            if ($(addReminderForm.reminderName).hasClass('ng-valid') && $(addReminderForm.reminderDescription).hasClass('ng-valid') && $scope.reminder.day !== undefined && $scope.reminder.month !== undefined) {
                $scope.isChecked = false;
                $scope.errorMessage = "";
                $scope.working = true;
                var reminder = $scope.reminder;
                var user = $scope.loggedUser;

                user.reminders.addReminder(reminder.name,reminder.description, reminder.day.value, reminder.month.value, user.access_token,
                    function (result, errorMessage) {
                        if (result) {
                            $scope.processSuccess = true;
                            $scope.working = false;
                            $scope.isChecked = false;
                            //kissmetricsHelper.recordEvent('Hatırlatma ekledi');
                            $timeout(function () {
                                $scope.setSignSection('profil');
                                $scope.closeSection();
                                $scope.processSuccess = false;
                            }, 2000);
                        } else {
                            $scope.processSuccess = false;
                            $scope.isChecked = true;
                            $scope.working = false;
                            $scope.errorMessage = errorMessage;
                        }
                    }
                );
            } else {
                $scope.working = false;
                $scope.isChecked = true;
                if (!($(addReminderForm.reminderName).hasClass('ng-valid')))
                    otherExceptions.sendException("Add Reminder", "Eksik Bilgi- hatırlatma adı");
                else if (!($(addReminderForm.reminderDescription).hasClass('ng-valid')))
                    otherExceptions.sendException("Add Reminder", "Eksik Bilgi- hatırlatma açıklama");
                else if ( $scope.reminder.day === undefined)
                    otherExceptions.sendException("Add Reminder", "Eksik Bilgi- hatırmatma gün");
                else if ( $scope.reminder.month === undefined)
                    otherExceptions.sendException("Add Reminder", "Eksik Bilgi- hatırlatma ay");
                else
                    otherExceptions.sendException("Add Reminder",  "Eksik Bilgi");
                if(translateHelper.getCurrentLang() == 'en'){
                    $scope.errorMessage = "Missing information";
                }
                else{
                    $scope.errorMessage = "Hatırlatma Bilgilerine İhtiyacımız Var";
                }
            }
        };

        $scope.closeSection = function (){
            $scope.resetForm($scope.addReminderForm);
            $scope.reminder = {};
        };
    });
