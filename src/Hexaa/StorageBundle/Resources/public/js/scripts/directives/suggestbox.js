'use strict';

angular.module('hexaaApp')
  .directive('suggestBox', function() {
        return {
        restrict: 'A',
        scope: {
            elements: '=',
            chosen: '=',
            placeholder: '='
        },
        link: function(scope, elm, attr) {
            // INIT
            scope.$watch("elements", function(newValue, oldValue) {
                scope.availableElements = scope.elements;
                scope.searchElement = "";
            });

            scope.update = function() {
                if (scope.searchElement === "") {
                    scope.availableElements = scope.elements;
                } else {
                    scope.availableElements = scope.filterUsers(scope.elements);
                }
            };

            scope.filterUsers = function(elements) {
                var availableElements = [];
                angular.forEach(elements, function(element) {
                    if (element.name.indexOf(scope.searchElement) != -1) {
                        this.push(element);
                    }
                }, availableElements);
                return availableElements;
            };

            scope.select = function() {
                scope.$parent.suggestBoxData[scope.chosen] = scope.model;  
            };
        },
        template:
        '<div class="form-group">' +
            '<input type="text" ng-model="searchElement" ng-attr-placeholder="{{placeholder}}" ng-model-onblur ng-change="update()" class="form-control searchUser"/>' +
            '<select multiple ng-multiple="true" class="form-control selectUser" ng-click="select()" ng-model="model" ng-options="element.name for element in availableElements"></select>' +
        '</div>'
    };
});
