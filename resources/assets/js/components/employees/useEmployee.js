import React, {useState} from 'react'

export default function useEmployee() {
    const [activityLog, setActivityLog] = useState(undefined)
    const [birthDate, setBirthDate] = useState(new Date())
    const [companyName, setCompanyName] = useState('')
    const [deliveryCommission, setDeliveryCommission] = useState('')
    const [driversLicenseExpirationDate, setDriversLicenseExpirationDate] = useState(new Date())
    const [driversLicenseNumber, setDriversLicenseNumber] = useState('')
    const [employeeId, setEmployeeId] = useState(undefined)
    const [employeeNumber, setEmployeeNumber] = useState('')
    const [employeePermissions, setEmployeePermissions] = useState('')
    const [insuranceExpirationDate, setInsuranceExpirationDate] = useState(new Date())
    const [insuranceNumber, setInsuranceNumber] = useState('')
    const [isDriver, setIsDriver] = useState(false)
    const [isEnabled, setIsEnabled] = useState(true)
    const [licensePlateExpirationDate, setLicensePlateExpirationDate] = useState(new Date())
    const [licensePlateNumber, setLicensePlateNumber] = useState('')
    const [pickupCommission, setPickupCommission] = useState('')
    const [SIN, setSIN] = useState('')
    const [startDate, setStartDate] = useState(new Date())
    const [vehicleType, setVehicleType] = useState({})
    const [vehicleTypes, setVehicleTypes] = useState([])

    const collectAdvanced = () => {
        return {
            birth_date: birthDate.toISOString(),
            employee_number: employeeNumber,
            is_driver: isDriver,
            is_enabled: isEnabled,
            permissions: employeePermissions,
            sin: SIN,
            start_date: startDate.toISOString()
        }
    }

    const collectDriver = () => {
        return {
            company_name: companyName,
            delivery_commission: deliveryCommission,
            drivers_license_expiration_date: driversLicenseExpirationDate.toISOString(),
            drivers_license_number: driversLicenseNumber,
            insurance_expiration_date: insuranceExpirationDate.toISOString(),
            insurance_number: insuranceNumber,
            license_plate_number: licensePlateNumber,
            license_plate_expiration_date: licensePlateExpirationDate.toISOString(),
            pickup_commission: pickupCommission,
            vehicle_type: vehicleType
        }
    }

    const reset = () => {
        setActivityLog([])
        setBirthDate(Date.now())
        setCompanyName('')
        setDeliveryCommission('')
        setDriversLicenseExpirationDate(Date.now())
        setDriversLicenseNumber('')
        setEmployeeId('')
        setEmployeeNumber('')
        setEmployeePermissions([])
        setInsuranceExpirationDate(Date.now())
        setInsuranceNumber('')
        setIsDriver(false)
        setIsEnabled(true)
        setLicensePlateExpirationDate(Date.now())
        setLicensePlateNumber('')
        setPickupCommission('')
        setSIN('')
        setStartDate(Date.now())
        setUpdatedAt(null)
        setVehicleType(null)
    }

    const setup = employee => {
        setActivityLog(employee.activity_log.map(log => {
            return {...log, properties: JSON.parse(log.properties)}
        }))
        setBirthDate(Date.parse(employee.dob))
        setEmployeeId(employee.employee_id)
        setEmployeeNumber(employee.employee_number)
        setIsDriver(!!employee.is_driver)
        setIsEnabled(!!employee.is_enabled)
        setSIN(employee.sin)
        setStartDate(Date.parse(employee.start_date))
        setCompanyName(employee.company_name ?? '')
        setDeliveryCommission(employee.delivery_commission)
        setDriversLicenseNumber(employee.drivers_license_number)
        setDriversLicenseExpirationDate(Date.parse(employee.drivers_license_expiration_date))
        setEmployeePermissions(employee.employee_permissions)
        setInsuranceNumber(employee.insurance_number)
        setInsuranceExpirationDate(Date.parse(employee.insurance_expiration_date))
        setLicensePlateNumber(employee.license_plate_number)
        setLicensePlateExpirationDate(Date.parse(employee.license_plate_expiration_date))
        setPickupCommission(employee.pickup_commission)
        setVehicleType(employee.vehicle_types.find(type => type.selection_id == employee.vehicle_type))
        setVehicleTypes(employee.vehicle_types)
    }

    return {
        collectAdvanced,
        collectDriver,
        reset,
        setup,
        activityLog,
        birthDate,
        companyName,
        deliveryCommission,
        driversLicenseExpirationDate,
        driversLicenseNumber,
        employeeId,
        employeeNumber,
        employeePermissions,
        insuranceExpirationDate,
        insuranceNumber,
        isDriver,
        isEnabled,
        licensePlateNumber,
        licensePlateExpirationDate,
        pickupCommission,
        SIN,
        startDate,
        vehicleType,
        setActivityLog,
        setBirthDate,
        setCompanyName,
        setDeliveryCommission,
        setDriversLicenseExpirationDate,
        setDriversLicenseNumber,
        setEmployeeId,
        setEmployeeNumber,
        setEmployeePermissions,
        setInsuranceExpirationDate,
        setInsuranceNumber,
        setIsDriver,
        setIsEnabled,
        setLicensePlateNumber,
        setLicensePlateExpirationDate,
        setPickupCommission,
        setSIN,
        setStartDate,
        setVehicleType,
    }
}

