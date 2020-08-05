'use strict';

/**
 * @ngdoc service
 * @name bloomNFresh.deviceDetector
 * @description
 * # deviceDetector
 * Service in the bloomNFresh.
 */
angular.module('app')
  .service('deviceDetector', function () {
        var browserInfo =
            (function () {
                var ua = navigator.userAgent, tem,
                    M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
                if (/trident/i.test(M[1])) {
                    tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
                    return 'IE ' + (tem[1] || '');
                }
                if (M[1] === 'Chrome') {
                    tem = ua.match(/\b(OPR|Edge)\/(\d+)/);
                    if (tem != null) return tem.slice(1).join(' ').replace('OPR', 'Opera');
                }
                M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];
                if ((tem = ua.match(/version\/(\d+)/i)) != null) M.splice(1, 1, tem[1]);
                return { 'browser' : M[0], 'version' : M[1] };
            })();

        this.getBrowserInfo = function(){
            return browserInfo;
        };

        this.getMobileInfo = function(){
            var str = navigator.userAgent;

            console.log(navigator);

            var res = str.match(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/);
            return res[0];
        };

        this.isTablet = function(){
            return (navigator.userAgent.match(/Tablet|iPad|iPod/i) && window.innerWidth <= 1100 && window.innerHeight > 750);
        };

        this.isMobile = function(){
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        };


        this.whichPlatform = function(){
            if( this.isTablet() ){
                return 't';
            }
            else if( this.isMobile() ){
                return 'm';
            }
            else{
                return 'd';
            }

        }
  });
