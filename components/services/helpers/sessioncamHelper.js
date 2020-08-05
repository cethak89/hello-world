'use strict';

/**
 * @ngdoc service
 * @name app.sessioncamHelper
 * @description
 * # sessioncamHelper
 * Service in the app.
 */

angular.module('app')
    .service('sessioncamHelper', function ($window) {
        this.pageChanged = function(pageName, pagePath){

            //if(!$window.sessioncamConfiguration)
                //$window.sessioncamConfiguration = new Object();

            //sessioncamConfiguration.SessionCamPath = pagePath;

            //if(pagePath)
              //  sessioncamConfiguration.SessionCamPath = pagePath;

          //  sessioncamConfiguration.SessionCamPageName = pageName;
        }


    });