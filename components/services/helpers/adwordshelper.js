'use strict';

/**
 * @ngdoc service
 * @name bloomNFresh.adwordsHelper
 * @description
 * # adwordsHelper
 * Service in the bloomNFresh.
 */
angular.module('app')
  .service('adwordsHelper', function ($window) {
        this.signUpTrack = function () {
            $window.google_trackConversion({
                google_conversion_id: 948671397,
                google_conversion_language: "en",
                google_conversion_format: "3",
                google_conversion_color: "ffffff",
                google_conversion_label : "zpI4CJ2zlV8QpaeuxAM",
                google_remarketing_only: false
            });
        };

        this.purchaseTrack = function (value) {
            $window.google_trackConversion({
                google_conversion_id: 948671397,
                google_conversion_language: "en",
                google_conversion_format: "3",
                google_conversion_color: "ffffff",
                google_conversion_label : "ajpFCK2Zil8QpaeuxAM",
                google_conversion_value : value,
                google_conversion_currency : "TRY",
                google_remarketing_only: false
            });
        }
  });
