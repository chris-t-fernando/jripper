$global:debugOn = $false

function debugEcho {
	param (
		[parameter(mandatory)]
		[string]$debugText
	)
	
	if ( $global:debugOn )
	{
		write-output "Debug: $debugText"
		
	}
	
}

if ( $args.count -gt 1 )
{
	write-host "Too many arguments.  Only one parameter is accepted, and that is --debug"
	exit
	
}

if ( $args.count -eq 1 )
{
	if ( $args[0] -eq "--debug" )
	{
		$global:debugOn = $true
		write-host "Starting script with --debug enabled"
		
	} else {
		write-host "Unknown argument.  Only one parameter is accepted, and that is --debug"
		exit
		
	}
	
}

$parameterVersion = Write-SSMParameter -name "/jripper-build/server-build-status" -type "string" -value "-1" -overwrite $true
$parameterVersion = Write-SSMParameter -name "/jripper-build/server-build-status-message" -type "string" -value "0" -overwrite $true

$userDataFile = 'jripper-build-userdata.txt'

# create instance 

$Script = Get-Content -Raw jripper-build-userdata.txt
$UserData = [System.Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($Script))

$instanceRequest = New-EC2Instance -ImageId ami-03d5c68bab01f3496 -MinCount 1 -MaxCount 1 -KeyName chris2 -SecurityGroupId sg-8b5c50ee -InstanceType t2.nano -userdata $userData -iaminstanceprofile_name jenkins-build-ec2roleapplied

debugecho ("Reservation ID is " + $instanceRequest.reservationId  + ", new instance ID is " + $instanceRequest.instances[0].instanceId)
start-sleep 10

# wait 60 seconds for the instance to be up
$wait = 60
$sofar = 0

while ( $sofar -lt $wait )
{
	if ( ((get-ec2instancestatus -instanceid $instancerequest.instances[0]).status).status -eq "ok" )
	{
		debugecho "New instance is up"
		break
		
	} else {
		debugecho "New instance is not up yet, sleeping 10 seconds"
		
	}
	start-sleep -seconds 10
	$sofar += 10
		
}

# now wait for user data
$wait = 240
$sofar = 0

while ( $sofar -lt $wait )
{
	if ( (get-SSMParameter -name "/jripper-build/server-build-status").value -eq "stage1" )
	{
		debugecho "New instance has finished user-data"
		break
		
	} else {
		if ( (get-SSMParameter -name "/jripper-build/server-build-status-message").value -ne "0" ) 
		{
			debugecho ("Status update: " + (get-SSMParameter -name "/jripper-build/server-build-status-message").value )
			$parameterVersion = Write-SSMParameter -name "/jripper-build/server-build-status-message" -type "string" -value "0" -overwrite $true
			
		} else {
			debugecho "New instance is still running user-data, sleeping 10 seconds"
			
		}
		
		
	}
	
	start-sleep -seconds 10
	$sofar += 10
		
}

$reservation=""
$reservation = New-Object 'collections.generic.list[string]'
$reservation.add($instanceRequest.reservationId)
$filter_reservation = New-Object Amazon.EC2.Model.Filter -Property @{Name = "reservation-id"; Values = $reservation}
write-output ("Finished building jripper on instance ID " + $instanceRequest.instances[0].instanceId + ", public IP is " + ((Get-EC2Instance -Filter $filter_reservation).instances).publicipaddress) 

