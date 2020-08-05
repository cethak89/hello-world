'use strict';

/**
 * @ngdoc service
 * @name app.scrollBarHelper
 * @description
 * # scrollBarHelper
 * Service in the bloomNFresh.
 */
angular.module('app')
  .service('scrollBarHelper', function () {
        this.reloadScrollBars = function() {
            document.documentElement.style.overflow = 'auto';  // firefox, chrome
            document.body.scroll = "yes"; // ie only
        };

        this.unloadScrollBars = function() {
            document.documentElement.style.overflow = 'hidden';  // firefox, chrome
            document.body.scroll = "no"; // ie only
        };
  });
