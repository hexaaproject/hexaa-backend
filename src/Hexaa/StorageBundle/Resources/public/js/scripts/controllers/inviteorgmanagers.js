var InviteManagersCtrl = function($scope, $modal) {

  $scope.organizations = $scope.$parent.organizations;
  $scope.selectedOrganization = $scope.$parent.selectedOrganization;

  $scope.inviteManagers = function() {

    var modalInstance = $modal.open({
      templateUrl: 'inviteManagersModal.html',
      controller: ModalInstanceCtrl,
      size: 'lg',
      resolve: {
        organizationName: function() {
          return $scope.organizations[$scope.selectedOrganization].properties.name;
        }
      }
    });

    modalInstance.result.then(function(invitation) {
      $scope.$parent.addAlert('success', 'Invitation sent!');
      console.log(invitation);
    });
  };
};

var ModalInstanceCtrl = function($scope, $modalInstance, organizationName) {
  
  $scope.organizationName = organizationName;
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