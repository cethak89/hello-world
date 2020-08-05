'use strict';

/**
 * @ngdoc service
 * @name app.momentHelper
 * @description
 * # momentHelper
 * Service in the bloomNFresh.
 */
angular.module('app')
    .service('momentHelper', function (translateHelper) {
        var localeLang = translateHelper.getCurrentLang();

        this.initCalendarSettings = function(){
            moment.locale('tr', {
                calendar: {
                    sameDay: 'D MMMM, [Bugün]',
                    nextDay: 'D MMMM, [Yarın]',
                    nextWeek: 'D MMMM, dddd',
                    lastDay: 'D MMMM, dddd',
                    lastWeek: 'D MMMM, dddd',
                    sameElse: 'D MMMM, dddd'
                }
            });

            moment.locale('en', {
                calendar: {
                    sameDay: 'D MMMM, [Today]',
                    nextDay: 'D MMMM, [Tomorrow]',
                    nextWeek: 'D MMMM, dddd',
                    lastDay: 'D MMMM, dddd',
                    lastWeek: 'D MMMM, dddd',
                    sameElse: 'D MMMM, dddd'
                }
            });
        };

        this.getCalenderDate = function(dateFromNow){
            return dateFromNow.calendar(moment());
        };

        this.getCurrentDate = function(momentObj){
            if(momentObj)
                return momentObj.locale(localeLang).day();
            else
                return moment().locale(localeLang).day();
        };

        this.isDaySunday = function(momentObj){
           return this.getCurrentDate(momentObj) === 0
        };

        this.addDate = function(addingDate,now){
            //console.log(moment().set('date', now.day).add(addingDate, 'days'));
            return moment().set('year', now.year).set('month', now.month).set('date', now.day ).set('hour', now.hour).set('minute', now.minute).set('second', now.second).add(addingDate, 'days');
        };

        this.getTime = function (format, momentObj) {
            if (momentObj && moment.isMoment(momentObj))
                return momentObj.locale(localeLang).format(format);
            else if (momentObj)
                return moment(momentObj).locale(localeLang).format(format);
            else
                return moment().locale(localeLang).format(format);
        };

        this.getMomentObj = function(date){
            return moment(date, 'YYYY-MM-DD HH:mm:ss').locale(localeLang);
        };

        this.returnMonths = function(){
            var months = [];

            for(var i=0; i<12; i++){
                var momentObj = moment().month(i).locale(localeLang);

                months.push({
                    display: momentObj.format("MMMM"),
                    value : i + 1
                })
            }

            return months;
        }
    });
