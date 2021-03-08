<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\UpsDeclaredValue\Rewrite\Ups\Model;

use Magento\Shipping\Model\Simplexml\Element;

/**
 * Class CarrierRewrite
 *
 * @package Magestore\UpsDeclaredValue\Rewrite\Ups\Model
 */
class CarrierRewrite extends \Magento\Ups\Model\Carrier
{
    /**
     * Form XML for shipment request
     *
     * @param \Magento\Framework\DataObject $request
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _formShipmentRequest(\Magento\Framework\DataObject $request)
    {
        $packageParams = $request->getPackageParams();
        $height = $packageParams->getHeight();
        $width = $packageParams->getWidth();
        $length = $packageParams->getLength();
        $weightUnits = $packageParams->getWeightUnits() == \Zend_Measure_Weight::POUND ? 'LBS' : 'KGS';
        $dimensionsUnits = $packageParams->getDimensionUnits() == \Zend_Measure_Length::INCH ? 'IN' : 'CM';

        $itemsDesc = [];
        $itemsShipment = $request->getPackageItems();
        foreach ($itemsShipment as $itemShipment) {
            $item = new \Magento\Framework\DataObject();
            $item->setData($itemShipment);
            $itemsDesc[] = $item->getName();
        }

        $xmlRequest = $this->_xmlElFactory->create(
            ['data' => '<?xml version = "1.0" ?><ShipmentConfirmRequest xml:lang="en-US"/>']
        );
        $requestPart = $xmlRequest->addChild('Request');
        $requestPart->addChild('RequestAction', 'ShipConfirm');
        $requestPart->addChild('RequestOption', 'nonvalidate');

        $shipmentPart = $xmlRequest->addChild('Shipment');
        if ($request->getIsReturn()) {
            $returnPart = $shipmentPart->addChild('ReturnService');
            // UPS Print Return Label
            $returnPart->addChild('Code', '9');
        }
        $shipmentPart->addChild('Description', substr(implode(' ', $itemsDesc), 0, 35));
        //empirical

        $shipperPart = $shipmentPart->addChild('Shipper');
        if ($request->getIsReturn()) {
            $shipperPart->addChild('Name', $request->getRecipientContactCompanyName());
            $shipperPart->addChild('AttentionName', $request->getRecipientContactPersonName());
            $shipperPart->addChild('ShipperNumber', $this->getConfigData('shipper_number'));
            $shipperPart->addChild('PhoneNumber', $request->getRecipientContactPhoneNumber());

            $addressPart = $shipperPart->addChild('Address');
            $addressPart->addChild('AddressLine1', $request->getRecipientAddressStreet());
            $addressPart->addChild('AddressLine2', $request->getRecipientAddressStreet2());
            $addressPart->addChild('City', $request->getRecipientAddressCity());
            $addressPart->addChild('CountryCode', $request->getRecipientAddressCountryCode());
            $addressPart->addChild('PostalCode', $request->getRecipientAddressPostalCode());
            if ($request->getRecipientAddressStateOrProvinceCode()) {
                $addressPart->addChild('StateProvinceCode', $request->getRecipientAddressStateOrProvinceCode());
            }
        } else {
            $shipperPart->addChild('Name', $request->getShipperContactCompanyName());
            $shipperPart->addChild('AttentionName', $request->getShipperContactPersonName());
            $shipperPart->addChild('ShipperNumber', $this->getConfigData('shipper_number'));
            $shipperPart->addChild('PhoneNumber', $request->getShipperContactPhoneNumber());

            $addressPart = $shipperPart->addChild('Address');
            $addressPart->addChild('AddressLine1', $request->getShipperAddressStreet());
            $addressPart->addChild('AddressLine2', $request->getShipperAddressStreet2());
            $addressPart->addChild('City', $request->getShipperAddressCity());
            $addressPart->addChild('CountryCode', $request->getShipperAddressCountryCode());
            $addressPart->addChild('PostalCode', $request->getShipperAddressPostalCode());
            if ($request->getShipperAddressStateOrProvinceCode()) {
                $addressPart->addChild('StateProvinceCode', $request->getShipperAddressStateOrProvinceCode());
            }
        }

        $shipToPart = $shipmentPart->addChild('ShipTo');
        $shipToPart->addChild('AttentionName', $request->getRecipientContactPersonName());
        $shipToPart->addChild(
            'CompanyName',
            $request->getRecipientContactCompanyName() ? $request->getRecipientContactCompanyName() : 'N/A'
        );
        $shipToPart->addChild('PhoneNumber', $request->getRecipientContactPhoneNumber());

        $addressPart = $shipToPart->addChild('Address');
        $addressPart->addChild('AddressLine1', $request->getRecipientAddressStreet1());
        $addressPart->addChild('AddressLine2', $request->getRecipientAddressStreet2());
        $addressPart->addChild('City', $request->getRecipientAddressCity());
        $addressPart->addChild('CountryCode', $request->getRecipientAddressCountryCode());
        $addressPart->addChild('PostalCode', $request->getRecipientAddressPostalCode());
        if ($request->getRecipientAddressStateOrProvinceCode()) {
            $addressPart->addChild('StateProvinceCode', $request->getRecipientAddressRegionCode());
        }
        if ($this->getConfigData('dest_type') == 'RES') {
            $addressPart->addChild('ResidentialAddress');
        }

        if ($request->getIsReturn()) {
            $shipFromPart = $shipmentPart->addChild('ShipFrom');
            $shipFromPart->addChild('AttentionName', $request->getShipperContactPersonName());
            $shipFromPart->addChild(
                'CompanyName',
                $request->getShipperContactCompanyName() ? $request
                    ->getShipperContactCompanyName() : $request
                    ->getShipperContactPersonName()
            );
            $shipFromAddress = $shipFromPart->addChild('Address');
            $shipFromAddress->addChild('AddressLine1', $request->getShipperAddressStreet1());
            $shipFromAddress->addChild('AddressLine2', $request->getShipperAddressStreet2());
            $shipFromAddress->addChild('City', $request->getShipperAddressCity());
            $shipFromAddress->addChild('CountryCode', $request->getShipperAddressCountryCode());
            $shipFromAddress->addChild('PostalCode', $request->getShipperAddressPostalCode());
            if ($request->getShipperAddressStateOrProvinceCode()) {
                $shipFromAddress->addChild('StateProvinceCode', $request->getShipperAddressStateOrProvinceCode());
            }

            $addressPart = $shipToPart->addChild('Address');
            $addressPart->addChild('AddressLine1', $request->getShipperAddressStreet1());
            $addressPart->addChild('AddressLine2', $request->getShipperAddressStreet2());
            $addressPart->addChild('City', $request->getShipperAddressCity());
            $addressPart->addChild('CountryCode', $request->getShipperAddressCountryCode());
            $addressPart->addChild('PostalCode', $request->getShipperAddressPostalCode());
            if ($request->getShipperAddressStateOrProvinceCode()) {
                $addressPart->addChild('StateProvinceCode', $request->getShipperAddressStateOrProvinceCode());
            }
            if ($this->getConfigData('dest_type') == 'RES') {
                $addressPart->addChild('ResidentialAddress');
            }
        }

        $servicePart = $shipmentPart->addChild('Service');
        $servicePart->addChild('Code', $request->getShippingMethod());
        $packagePart = $shipmentPart->addChild('Package');
        $packagePart->addChild('Description', substr(implode(' ', $itemsDesc), 0, 35));
        //empirical
        $packagePart->addChild('PackagingType')->addChild('Code', $request->getPackagingType());
        $packageWeight = $packagePart->addChild('PackageWeight');
        $packageWeight->addChild('Weight', $request->getPackageWeight());
        $packageWeight->addChild('UnitOfMeasurement')->addChild('Code', $weightUnits);

        // set dimensions
        if ($length || $width || $height) {
            $packageDimensions = $packagePart->addChild('Dimensions');
            $packageDimensions->addChild('UnitOfMeasurement')->addChild('Code', $dimensionsUnits);
            $packageDimensions->addChild('Length', $length);
            $packageDimensions->addChild('Width', $width);
            $packageDimensions->addChild('Height', $height);
        }

        // ups support reference number only for domestic service
        if ($this->_isUSCountry(
                $request->getRecipientAddressCountryCode()
            ) && $this->_isUSCountry(
                $request->getShipperAddressCountryCode()
            )
        ) {
            if ($request->getReferenceData()) {
                $referenceData = $request->getReferenceData() . $request->getPackageId();
            } else {
                $referenceData = 'Order #' .
                    $request->getOrderShipment()->getOrder()->getIncrementId() .
                    ' P' .
                    $request->getPackageId();
            }
            $referencePart = $packagePart->addChild('ReferenceNumber');
            $referencePart->addChild('Code', '02');
            $referencePart->addChild('Value', $referenceData);
        }

        $deliveryConfirmation = $packageParams->getDeliveryConfirmation();
        if ($deliveryConfirmation) {
            /** @var $serviceOptionsNode Element */
            $serviceOptionsNode = null;
            switch ($this->_getDeliveryConfirmationLevel($request->getRecipientAddressCountryCode())) {
                case self::DELIVERY_CONFIRMATION_PACKAGE:
                    $serviceOptionsNode = $packagePart->addChild('PackageServiceOptions');
                    break;
                case self::DELIVERY_CONFIRMATION_SHIPMENT:
                    $serviceOptionsNode = $shipmentPart->addChild('ShipmentServiceOptions');
                    break;
                default:
                    break;
            }
            if (!is_null($serviceOptionsNode)) {
                $serviceOptionsNode->addChild(
                    'DeliveryConfirmation'
                )->addChild(
                    'DCISType',
                    $packageParams->getDeliveryConfirmation()
                );
            }
            // Add Declared Value to UPS Shipping Label
            $this->addDeclaredValue($serviceOptionsNode, $request);
        } else {
            $serviceOptionsNode = $packagePart->addChild('PackageServiceOptions');
            $this->addDeclaredValue($serviceOptionsNode, $request);
            // End Declared Value to UPS Shipping Label
        }

        $shipmentPart->addChild('PaymentInformation')
            ->addChild('Prepaid')
            ->addChild('BillShipper')
            ->addChild('AccountNumber', $this->getConfigData('shipper_number'));

        if ($request->getPackagingType() != $this->configHelper->getCode('container', 'ULE') &&
            $request->getShipperAddressCountryCode() == self::USA_COUNTRY_ID &&
            ($request->getRecipientAddressCountryCode() == 'CA' ||
                $request->getRecipientAddressCountryCode() == 'PR')
        ) {
            $invoiceLineTotalPart = $shipmentPart->addChild('InvoiceLineTotal');
            $invoiceLineTotalPart->addChild('CurrencyCode', $request->getBaseCurrencyCode());
            $invoiceLineTotalPart->addChild('MonetaryValue', ceil($packageParams->getCustomsValue()));
        }

        $labelPart = $xmlRequest->addChild('LabelSpecification');
        $labelPart->addChild('LabelPrintMethod')->addChild('Code', 'GIF');
        $labelPart->addChild('LabelImageFormat')->addChild('Code', 'GIF');

        return $xmlRequest->asXml();
    }

    /**
     * Add declared value
     *
     * @param $serviceOptionsNode
     * @param $request
     */
    public function addDeclaredValue($serviceOptionsNode, $request)
    {
        // Add Declared Value to UPS Shipping Label
        $declaredValue = 0;
        $currencyCode = $request->getOrderShipment()->getOrder()->getOrderCurrencyCode();
        foreach ($request->getOrderShipment()->getAllItems() as $item) {
            $declaredValue += $item->getPrice() * $item->getQty();
        }

        $insuredValue = $serviceOptionsNode->addChild('InsuredValue');
        $insuredValue->addChild('MonetaryValue', $declaredValue);
        $insuredValue->addChild('CurrencyCode', $currencyCode);
        // End Declared Value to UPS Shipping Label
    }
}
