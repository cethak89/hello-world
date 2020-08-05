'use strict';

/**
 * @ngdoc service
 * @name bloomNFresh.userReminders
 * @description
 * # userReminders
 * Service in the app.
 */
angular.module('app')
  .service('userReminders', ['$http',function($http) {
        $http.defaults.headers.post["Content-Type"] = "application/json";

        var reminders= [];

        var initReminders = function(access_token,callback){
            var data = {
                access_token : access_token
            };
            $http.post(webServer + '/user-get-reminder',data).success(function (response) {
                reminders = response;
                if(callback)
                    callback(true);
            }).error(function(data, status) {
                if(callback)
                    callback(false);
            });
        };

        return {
            getReminders : function() {
                return reminders;
            },
            setReminders : function(values) {
                reminders = values;
            },
            initReminders : function(access_token, callbackFunc) {
               initReminders(access_token,callbackFunc);
            },
            addReminder : function(reminderName, reminderDescription, reminderDay, reminderMonth, access_token, callbackFunction){
                var newReminderInfo ={
                    "name" : reminderName,
                    "description" : reminderDescription,
                    "reminder_day" : reminderDay,
                    "reminder_month" : reminderMonth,
                    "access_token" : access_token
                };

                $http.post(webServer + '/insert-reminder',newReminderInfo).success(function (data)
                {
                    //initReminders(access_token);
                    callbackFunction(true);
                }).error( function(data)
                {
                    callbackFunction(false, data.description);
                });
            },
            removeReminder: function(reminderId, access_token,callbackFunc){
                var reminderInfo = {
                    id : reminderId,
                    access_token : access_token
                };

                $http.post(webServer+ '/user-delete-reminder',reminderInfo).success(function (data)
                {
                    initReminders(access_token,callbackFunc);
                }).error( function(data)
                {
                    callbackFunc(false, data.description);
                });
            }
        }
    }]);
