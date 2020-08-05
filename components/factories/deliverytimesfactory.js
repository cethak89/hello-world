'use strict';

/**
 * @ngdoc service
 * @name bloomNFresh.deliveryTimesFactory
 * @description
 * # deliveryTimesFactory
 * Factory in the app.
 */
angular.module('app')
  .factory('deliveryTimesFactory', function ($http,otherExceptions,momentHelper) {
        $http.defaults.headers.post["Content-Type"] = "application/json";

        var deliveryTimes = [];
        var now;

        var initDeliveryTimes = $http.get(webServer + '/delivery-time').success(function (response) {
            deliveryTimes = response.data;
        }).error(function(data){
            otherExceptions.sendException("deliveryTimesFactory",  "Gönderim Bölgelerini Çekerken Serverdan Hata Döndü");
        });

    // Public API here
    return {
        initDeliveryTimes: initDeliveryTimes,
        getDeliveryTimes: function( productId, cityId , callback){
            $http.get(webServer + '/delivery-time-with-know/' + productId + '/' + cityId ).success(function (response) {
                deliveryTimes = response.data;
                now = response.now;
                callback();
            }).error(function(data){
                otherExceptions.sendException("deliveryTimesFactory",  "Gönderim Bölgelerini Çekerken Serverdan Hata Döndü");
            })
        },
        getDeliveryTimesAsDisplayFormat: function(continentId){

            var newDeliveryTimes = [];

            momentHelper.initCalendarSettings();

            for (var index=0; index < deliveryTimes.length; index++) {
                if(deliveryTimes[index].continent_id === continentId){
                    var i = deliveryTimes[index].day_number;

                    //console.log(now);

                    var momentObj = momentHelper.addDate(i,now);
                    //if(momentHelper.isDaySunday(momentObj))
                    //    continue;
                    momentHelper.isDaySunday(momentObj);
                    var display_date = momentHelper.getCalenderDate(momentObj);

                    newDeliveryTimes.push({
                        continent_id : deliveryTimes[index].continent_id,
                        extra_minutes: deliveryTimes[index].extra_minutes,
                        hours: deliveryTimes[index].hours,
                        display_date : display_date,
                        value : momentHelper.getTime('DD-MM-YYYY',momentObj)
                    });
                }

            }

            return newDeliveryTimes;
        },
        getDeliveryTime : function(continentId){
            for(var i=0; i<deliveryTimes.length; i++)
                if(deliveryTimes[i].continent_id === continentId)
                    return deliveryTimes[i];
        }
    };
  });
