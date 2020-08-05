'use strict';

angular.module('studioBloom')
    .directive('studioBloomInfo', function () {
        return {
            templateUrl: '../../views/bf-utility-pages/studioBloomPurchaseInfo-v1.html',
            restrict: 'E',
            scope: {
                flower: "=",
                campaign: "=",
                section: "=",
                receiver: "=?",
                note: "=?"
            }
        };
    });
