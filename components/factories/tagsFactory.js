/**
 * Created by furkan on 24.04.2015.
 */

angular.module('app')
    .factory("tagFactory", function ($http, translateHelper) {
        var tags = [];
        var pages = [];

        var getTags = function () {
            if (tags.length > 0) {
                return tags;
            }
            else {
                return initTags;
            }
        };

        var getPages = function () {
            if (pages.length > 0) {
                return pages;
            }
            else {
                return initPages;
            }
        };


        var getTag = function (tagName) {
            var tag = {};

            tags.forEach(function (tagObj) {

                if (tagObj.tags_name === tagName) {
                    tag = tagObj;
                    return;
                }
            });

            return tag;
        };

        var getTagsForAFlower = function (flowerTags) {
            var allTags = getTags();

            allTags.forEach(function (tagObj) {

                var i = 0;
                for (; i < flowerTags.length; i++) {
                    if (tagObj.tags_name === flowerTags[i].tags_name) {
                        tagObj.isTagActive = true;
                        break;
                    }
                }

                if (i === flowerTags.length) {
                    tagObj.isTagActive = false;
                }
            });

            return allTags;
        };

        var initTags = $http.get(webServer + '/get-tags/' + translateHelper.getCurrentLang()).success(function (result) {
            tags = result.data;
        }).error(function (result) {
            console.log("err");
        });


        var initPages = $http.get(webServer + '/get-pages' ).success(function (result) {
            pages = result.data;
        }).error(function (result) {
            console.log("err");
        });

        return {
            initTags: initTags,
            initPages: initPages,
            getTags: getTags,
            getPages: getPages,
            getTagsForAFlower: function (flowerTags) {
                return getTagsForAFlower(flowerTags);
            },
            getTag: getTag
        };
    });