var InviteManagersCtrl = function($scope, $modal) {

  $scope.services = $scope.$parent.services;
  $scope.selectedService = $scope.$parent.selectedService;

  $scope.inviteManagers = function() {

    var modalInstance = $modal.open({
      templateUrl: 'inviteManagersModal.html',
      controller: ModalInstanceCtrl,
      size: 'lg',
      resolve: {
        serviceName: function() {
          return $scope.services[$scope.selectedService].properties.name;
        }
      }
    });

    modalInstance.result.then(function(invitation) {
      $scope.$parent.addAlert('success', 'Invitation sent!');
      console.log(invitation);
    });
  };
};

var ModalInstanceCtrl = function($scope, $modalInstance, serviceName) {
  
  $scope.serviceName = serviceName;
  $scope.invitation = {
    invitedManagers: "",
    inviteText: ""
  };

  $scope.ok = function() {
    $modalInstance.close($scope.invitation);
  };

  $scope.cancel = function() {
    $modalInstance.dismiss('cancel');
  };
};