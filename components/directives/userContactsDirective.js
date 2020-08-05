'use strict';

/**
 * @ngdoc directive
 * @name menuModule.directive:userContactsDirective
 * @description
 * # userContactsDirective
 */
angular.module('menuModule')
    .directive('userContacts', function ($rootScope, $timeout) {
        return {
            restrict: 'E',
            require: '^rightMenus',
            controller: function ($scope) {
                $scope.getIcon = function (index, contact) {
                    return (index % 4);
                };
                $scope.isContactExist = function () {
                    if ($scope.contacts)
                        return $scope.contacts.length > 0;
                    else
                        return false;
                };
                $scope.checkCard = function (index) {
                    return (index + 1) % 3 === 0;
                };
                $scope.callEditContact = function (contact) {
                    $rootScope.$broadcast('EDIT_CONTACT', contact);
                    $scope.setSignSection('kisiEkle');
                };
                $scope.sendFlowerToContact = function (contact) {
                    $timeout(function () {
                        $rootScope.$broadcast('SEND_FLOWER_TO_CONTACT', contact);
                    }, 500);
                    $scope.setSignSection('');
                }
            },
            templateUrl: '../../views/menu-views/userContactsTab-v2.0.html'
        };
    });
