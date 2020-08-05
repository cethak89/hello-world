'use strict';

/**
 * @ngdoc service
 * @name app.textareaHelper
 * @description
 * # textareaHelper
 * Service in the bloomNFresh.
 */
angular.module('app')
    .service('textareaHelper', function () {

        this.checkMaxLength = function(){
            $('textarea[maxlength]').on('keyup blur', function () {
                // Store the maxlength and value of the field.
                var maxlength = $(this).attr('maxlength');
                var val = $(this).val();

                // Trim the field if it has content over the maxlength.
                if (val.length > maxlength) {
                    $(this).val(val.slice(0, maxlength));
                }
            });
        };
    });
