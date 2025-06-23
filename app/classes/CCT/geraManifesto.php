<?php

namespace App\classes\CCT;

use SimpleXMLElement;
use DOMDocument;

class geraManifesto
{
    public function geraXfzb($data)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<rsm:HouseWaybill xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:rsm="iata:housewaybill:1"
    xmlns:ccts="urn:un:unece:uncefact:documentation:standard:CoreComponentsTechnicalSpecification:2"
    xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:8"
    xmlns:ram="iata:datamodel:3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="iata:housewaybill:1 HouseWaybill_1.xsd"></rsm:HouseWaybill>');

        // MessageHeaderDocument
        $messageHeader = $xml->addChild('rsm:MessageHeaderDocument', null, 'iata:housewaybill:1');
        $messageHeader->addChild('ram:ID', $data['message_header']['id'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:Name', $data['message_header']['name'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:TypeCode', $data['message_header']['type_code'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:IssueDateTime', $data['message_header']['issue_datetime'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:PurposeCode', $data['message_header']['purpose_code'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:VersionID', $data['message_header']['version_id'], 'iata:datamodel:3');

        $senderParty = $messageHeader->addChild('ram:SenderParty', null, 'iata:datamodel:3');
        $senderParty->addChild('ram:PrimaryID', $data['message_header']['sender_party']['primary_id'], 'iata:datamodel:3')
            ->addAttribute('schemeID', $data['message_header']['sender_party']['scheme_id']);

        $recipientParty = $messageHeader->addChild('ram:RecipientParty', null, 'iata:datamodel:3');
        $recipientParty->addChild('ram:PrimaryID', $data['message_header']['recipient_party']['primary_id'], 'iata:datamodel:3')
            ->addAttribute('schemeID', $data['message_header']['recipient_party']['scheme_id']);

        // BusinessHeaderDocument
        $businessHeader = $xml->addChild('rsm:BusinessHeaderDocument', null, 'iata:housewaybill:1');
        $businessHeader->addChild('ram:ID', $data['business_header']['id'], 'iata:datamodel:3');

        $signatoryConsignor = $businessHeader->addChild('ram:SignatoryConsignorAuthentication', null, 'iata:datamodel:3');
        $signatoryConsignor->addChild('ram:Signatory', $data['business_header']['signatory_consignor'], 'iata:datamodel:3');

        $signatoryCarrier = $businessHeader->addChild('ram:SignatoryCarrierAuthentication', null, 'iata:datamodel:3');
        $signatoryCarrier->addChild('ram:ActualDateTime', $data['business_header']['signatory_carrier']['actual_datetime'], 'iata:datamodel:3');
        $signatoryCarrier->addChild('ram:Signatory', $data['business_header']['signatory_carrier']['signatory'], 'iata:datamodel:3');

        // MasterConsignment
        $masterConsignment = $xml->addChild('rsm:MasterConsignment', null, 'iata:housewaybill:1');
        $masterConsignment->addChild('ram:IncludedTareGrossWeightMeasure', $data['master_consignment']['gross_weight']['weight'], 'iata:datamodel:3')
            ->addAttribute('unitCode', $data['master_consignment']['gross_weight']['unit_code']);

        $transportContract = $masterConsignment->addChild('ram:TransportContractDocument', null, 'iata:datamodel:3');
        $transportContract->addChild('ram:ID', $data['master_consignment']['transport_contract']['id'], 'iata:datamodel:3');

        $originLocation = $masterConsignment->addChild('ram:OriginLocation', null, 'iata:datamodel:3');
        $originLocation->addChild('ram:ID', $data['master_consignment']['origin'], 'iata:datamodel:3');

        $finalDestination = $masterConsignment->addChild('ram:FinalDestinationLocation', null, 'iata:datamodel:3');
        $finalDestination->addChild('ram:ID', $data['master_consignment']['destination'], 'iata:datamodel:3');

        // IncludedHouseConsignment - com todos os campos obrigatórios
        $houseConsignment = $masterConsignment->addChild('ram:IncludedHouseConsignment', null, 'iata:datamodel:3');

        // Campos obrigatórios na ordem correta
        $houseConsignment->addChild('ram:NilCarriageValueIndicator', $data['master_consignment']['house_consignment']['nil_carriage_value'], 'iata:datamodel:3');
        if (!empty($data['master_consignment']['house_consignment']['declared_value']['amount'])) {
            $houseConsignment->addChild('ram:DeclaredValueForCarriageAmount', $data['master_consignment']['house_consignment']['declared_value']['amount'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['declared_value']['currency']);
        }
        // Campos adicionados para atender à validação

        if (!empty($data['master_consignment']['house_consignment']['declared_value_customs']['amount'])) {
            $houseConsignment->addChild('ram:NilCustomsValueIndicator', $data['master_consignment']['house_consignment']['nil_customs_value'], 'iata:datamodel:3');
            $houseConsignment->addChild('ram:DeclaredValueForCustomsAmount', $data['master_consignment']['house_consignment']['declared_value_customs']['amount'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['declared_value_customs']['currency']);
        }

        if (!empty($data['master_consignment']['house_consignment']['declared_value_customs']['amount'])) {
            $houseConsignment->addChild('ram:NilInsuranceValueIndicator', $data['master_consignment']['house_consignment']['nil_insurance_value'], 'iata:datamodel:3');
            $houseConsignment->addChild('ram:InsuranceValueAmount', $data['master_consignment']['house_consignment']['insurance_value']['amount'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['insurance_value']['currency']);
        }

        $houseConsignment->addChild('ram:TotalChargePrepaidIndicator', $data['master_consignment']['house_consignment']['total_charge_prepaid'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:WeightTotalChargeAmount', $data['master_consignment']['house_consignment']['weight_total_charge']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['weight_total_charge']['currency']);

        $houseConsignment->addChild('ram:ValuationTotalChargeAmount', $data['master_consignment']['house_consignment']['valuation_total_charge']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['valuation_total_charge']['currency']);

        $houseConsignment->addChild('ram:TaxTotalChargeAmount', $data['master_consignment']['house_consignment']['tax_total_charge']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['tax_total_charge']['currency']);

        $houseConsignment->addChild('ram:TotalDisbursementPrepaidIndicator', $data['master_consignment']['house_consignment']['total_disbursement_prepaid'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:AgentTotalDisbursementAmount', $data['master_consignment']['house_consignment']['agent_total_disbursement']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['agent_total_disbursement']['currency']);

        $houseConsignment->addChild('ram:CarrierTotalDisbursementAmount', $data['master_consignment']['house_consignment']['carrier_total_disbursement']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['carrier_total_disbursement']['currency']);

        $houseConsignment->addChild('ram:TotalPrepaidChargeAmount', $data['master_consignment']['house_consignment']['total_prepaid']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['total_prepaid']['currency']);

        $houseConsignment->addChild('ram:TotalCollectChargeAmount', $data['master_consignment']['house_consignment']['total_collect']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['total_collect']['currency']);

        $houseConsignment->addChild('ram:IncludedTareGrossWeightMeasure', $data['master_consignment']['house_consignment']['house_gross_weight']['value'], 'iata:datamodel:3')
            ->addAttribute('unitCode', $data['master_consignment']['house_consignment']['house_gross_weight']['unit_code']);

        // Demais campos
        $houseConsignment->addChild('ram:PackageQuantity', $data['master_consignment']['house_consignment']['package_quantity'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:TotalPieceQuantity', $data['master_consignment']['house_consignment']['total_piece_quantity'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:SummaryDescription', $data['master_consignment']['house_consignment']['summary_description'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:FreightRateTypeCode', $data['master_consignment']['house_consignment']['freight_rate_type'], 'iata:datamodel:3');

        // ConsignorParty
        $consignorParty = $houseConsignment->addChild('ram:ConsignorParty', null, 'iata:datamodel:3');
        $consignorParty->addChild('ram:Name', $data['master_consignment']['house_consignment']['consignor']['name'], 'iata:datamodel:3');

        $postalAddress = $consignorParty->addChild('ram:PostalStructuredAddress', null, 'iata:datamodel:3');
        $postalAddress->addChild('ram:PostcodeCode', $data['master_consignment']['house_consignment']['consignor']['address']['postcode'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:StreetName', $data['master_consignment']['house_consignment']['consignor']['address']['street'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CityName', $data['master_consignment']['house_consignment']['consignor']['address']['city'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CountryID', $data['master_consignment']['house_consignment']['consignor']['address']['country'], 'iata:datamodel:3');

        $tradeContact = $consignorParty->addChild('ram:DefinedTradeContact', null, 'iata:datamodel:3');
        $tradeContact->addChild('ram:DirectTelephoneCommunication', null, 'iata:datamodel:3')
            ->addChild('ram:CompleteNumber', $data['master_consignment']['house_consignment']['consignor']['contact']['phone'], 'iata:datamodel:3');

        // ConsigneeParty
        $consigneeParty = $houseConsignment->addChild('ram:ConsigneeParty', null, 'iata:datamodel:3');
        $consigneeParty->addChild('ram:Name', $data['master_consignment']['house_consignment']['consignee']['name'], 'iata:datamodel:3');

        $postalAddress = $consigneeParty->addChild('ram:PostalStructuredAddress', null, 'iata:datamodel:3');
        $postalAddress->addChild('ram:PostcodeCode', $data['master_consignment']['house_consignment']['consignee']['address']['postcode'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:StreetName', $data['master_consignment']['house_consignment']['consignee']['address']['street'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CityName', $data['master_consignment']['house_consignment']['consignee']['address']['city'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CountryID', $data['master_consignment']['house_consignment']['consignee']['address']['country'], 'iata:datamodel:3');

        $tradeContact = $consigneeParty->addChild('ram:DefinedTradeContact', null, 'iata:datamodel:3');
        $tradeContact->addChild('ram:DirectTelephoneCommunication', null, 'iata:datamodel:3')
            ->addChild('ram:CompleteNumber', $data['master_consignment']['house_consignment']['consignee']['contact']['phone'], 'iata:datamodel:3');

        // ApplicableTransportCargoInsurance
        $houseConsignment->addChild('ram:ApplicableTransportCargoInsurance', null, 'iata:datamodel:3')
            ->addChild('ram:CoverageInsuranceParty', null, 'iata:datamodel:3');

        // OriginLocation (dentro de IncludedHouseConsignment)
        $originLocation = $houseConsignment->addChild('ram:OriginLocation', null, 'iata:datamodel:3');
        $originLocation->addChild('ram:ID', $data['master_consignment']['origin_location']['id'], 'iata:datamodel:3');
        $originLocation->addChild('ram:Name', $data['master_consignment']['origin_location']['name'], 'iata:datamodel:3');

        // FinalDestinationLocation (dentro de IncludedHouseConsignment)
        $finalDestination = $houseConsignment->addChild('ram:FinalDestinationLocation', null, 'iata:datamodel:3');
        $finalDestination->addChild('ram:ID', $data['master_consignment']['final_destination']['id'], 'iata:datamodel:3');
        $finalDestination->addChild('ram:Name', $data['master_consignment']['final_destination']['name'], 'iata:datamodel:3');

        // Customs Notes
        foreach ($data['master_consignment']['house_consignment']['customs_notes'] as $note) {
            $customsNote = $houseConsignment->addChild('ram:IncludedCustomsNote', null, 'iata:datamodel:3');
            $customsNote->addChild('ram:ContentCode', $note['content_code'], 'iata:datamodel:3');
            $customsNote->addChild('ram:Content', $note['content'], 'iata:datamodel:3');
            $customsNote->addChild('ram:SubjectCode', $note['subject_code'], 'iata:datamodel:3');
            $customsNote->addChild('ram:CountryID', $note['country'], 'iata:datamodel:3');
        }

        // Consignment Items
        foreach ($data['master_consignment']['house_consignment']['items'] as $item) {
            $consignmentItem = $houseConsignment->addChild('ram:IncludedHouseConsignmentItem', null, 'iata:datamodel:3');
            $consignmentItem->addChild('ram:SequenceNumeric', $item['sequence'], 'iata:datamodel:3');

            $typeCode = $consignmentItem->addChild('ram:TypeCode', $item['type_code']['value'], 'iata:datamodel:3');
            $typeCode->addAttribute('listAgencyID', $item['type_code']['list_agency']);

            $consignmentItem->addChild('ram:GrossWeightMeasure', $item['gross_weight']['value'], 'iata:datamodel:3')
                ->addAttribute('unitCode', $item['gross_weight']['unit_code']);
            $consignmentItem->addChild('ram:GrossVolumeMeasure', $item['gross_volume']['value'], 'iata:datamodel:3')
                ->addAttribute('unitCode', $item['gross_volume']['unit_code']);
            $consignmentItem->addChild('ram:PackageQuantity', $item['package_quantity'], 'iata:datamodel:3');
            $consignmentItem->addChild('ram:PieceQuantity', $item['piece_quantity'], 'iata:datamodel:3');

            $natureIdentification = $consignmentItem->addChild('ram:NatureIdentificationTransportCargo', null, 'iata:datamodel:3');
            $natureIdentification->addChild('ram:Identification', $item['description'], 'iata:datamodel:3');

            $freightRate = $consignmentItem->addChild('ram:ApplicableFreightRateServiceCharge', null, 'iata:datamodel:3');
            $freightRate->addChild('ram:ChargeableWeightMeasure', $item['freight_rate']['chargeable_weight']['value'], 'iata:datamodel:3')
                ->addAttribute('unitCode', $item['freight_rate']['chargeable_weight']['unit_code']);
            $freightRate->addChild('ram:AppliedAmount', $item['freight_rate']['applied_amount']['value'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $item['freight_rate']['applied_amount']['currency']);
        }

        // Formatar o XML para saída
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        header('Content-Type: application/xml');
        echo $dom->saveXML();
    }

    public function geraXfhl($data)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
    <rsm:HouseWaybill xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:rsm="iata:housewaybill:1"
    xmlns:ccts="urn:un:unece:uncefact:documentation:standard:CoreComponentsTechnicalSpecification:2"
    xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:8"
    xmlns:ram="iata:datamodel:3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="iata:housewaybill:1 HouseWaybill_1.xsd"></rsm:HouseWaybill>');

        // MessageHeaderDocument
        $messageHeader = $xml->addChild('rsm:MessageHeaderDocument', null, 'iata:housewaybill:1');
        $messageHeader->addChild('ram:ID', $data['message_header']['id'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:Name', $data['message_header']['name'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:TypeCode', $data['message_header']['type_code'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:IssueDateTime', $data['message_header']['issue_datetime'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:PurposeCode', $data['message_header']['purpose_code'], 'iata:datamodel:3');
        $messageHeader->addChild('ram:VersionID', $data['message_header']['version_id'], 'iata:datamodel:3');

        $senderParty = $messageHeader->addChild('ram:SenderParty', null, 'iata:datamodel:3');
        $senderParty->addChild('ram:PrimaryID', $data['message_header']['sender_party']['primary_id'], 'iata:datamodel:3')
            ->addAttribute('schemeID', $data['message_header']['sender_party']['scheme_id']);

        $recipientParty = $messageHeader->addChild('ram:RecipientParty', null, 'iata:datamodel:3');
        $recipientParty->addChild('ram:PrimaryID', $data['message_header']['recipient_party']['primary_id'], 'iata:datamodel:3')
            ->addAttribute('schemeID', $data['message_header']['recipient_party']['scheme_id']);

        // BusinessHeaderDocument
        $businessHeader = $xml->addChild('rsm:BusinessHeaderDocument', null, 'iata:housewaybill:1');
        $businessHeader->addChild('ram:ID', $data['business_header']['id'], 'iata:datamodel:3');

        $signatoryConsignor = $businessHeader->addChild('ram:SignatoryConsignorAuthentication', null, 'iata:datamodel:3');
        $signatoryConsignor->addChild('ram:Signatory', $data['business_header']['signatory_consignor'], 'iata:datamodel:3');

        $signatoryCarrier = $businessHeader->addChild('ram:SignatoryCarrierAuthentication', null, 'iata:datamodel:3');
        $signatoryCarrier->addChild('ram:ActualDateTime', $data['business_header']['signatory_carrier']['actual_datetime'], 'iata:datamodel:3');
        $signatoryCarrier->addChild('ram:Signatory', $data['business_header']['signatory_carrier']['signatory'], 'iata:datamodel:3');

        // MasterConsignment
        $masterConsignment = $xml->addChild('rsm:MasterConsignment', null, 'iata:housewaybill:1');
        $masterConsignment->addChild('ram:IncludedTareGrossWeightMeasure', $data['master_consignment']['gross_weight']['weight'], 'iata:datamodel:3')
            ->addAttribute('unitCode', $data['master_consignment']['gross_weight']['unit_code']);

        $transportContract = $masterConsignment->addChild('ram:TransportContractDocument', null, 'iata:datamodel:3');
        $transportContract->addChild('ram:ID', $data['master_consignment']['transport_contract']['id'], 'iata:datamodel:3');

        $originLocation = $masterConsignment->addChild('ram:OriginLocation', null, 'iata:datamodel:3');
        $originLocation->addChild('ram:ID', $data['master_consignment']['origin'], 'iata:datamodel:3');

        $finalDestination = $masterConsignment->addChild('ram:FinalDestinationLocation', null, 'iata:datamodel:3');
        $finalDestination->addChild('ram:ID', $data['master_consignment']['destination'], 'iata:datamodel:3');

        // IncludedHouseConsignment - com todos os campos obrigatórios
        $houseConsignment = $masterConsignment->addChild('ram:IncludedHouseConsignment', null, 'iata:datamodel:3');

        // Campos obrigatórios na ordem correta
        $houseConsignment->addChild('ram:NilCarriageValueIndicator', $data['master_consignment']['house_consignment']['nil_carriage_value'], 'iata:datamodel:3');
        if (!empty($data['master_consignment']['house_consignment']['declared_value']['amount'])) {
            $houseConsignment->addChild('ram:DeclaredValueForCarriageAmount', $data['master_consignment']['house_consignment']['declared_value']['amount'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['declared_value']['currency']);
        }

        // Campos adicionados para atender à validação
        if (!empty($data['master_consignment']['house_consignment']['declared_value_customs']['amount'])) {
            $houseConsignment->addChild('ram:NilCustomsValueIndicator', $data['master_consignment']['house_consignment']['nil_customs_value'], 'iata:datamodel:3');
            $houseConsignment->addChild('ram:DeclaredValueForCustomsAmount', $data['master_consignment']['house_consignment']['declared_value_customs']['amount'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['declared_value_customs']['currency']);
        }

        if (!empty($data['master_consignment']['house_consignment']['insurance_value']['amount'])) {
            $houseConsignment->addChild('ram:NilInsuranceValueIndicator', $data['master_consignment']['house_consignment']['nil_insurance_value'], 'iata:datamodel:3');
            $houseConsignment->addChild('ram:InsuranceValueAmount', $data['master_consignment']['house_consignment']['insurance_value']['amount'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['insurance_value']['currency']);
        }

        $houseConsignment->addChild('ram:TotalChargePrepaidIndicator', $data['master_consignment']['house_consignment']['total_charge_prepaid'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:WeightTotalChargeAmount', $data['master_consignment']['house_consignment']['weight_total_charge']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['weight_total_charge']['currency']);

        $houseConsignment->addChild('ram:ValuationTotalChargeAmount', $data['master_consignment']['house_consignment']['valuation_total_charge']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['valuation_total_charge']['currency']);

        $houseConsignment->addChild('ram:TaxTotalChargeAmount', $data['master_consignment']['house_consignment']['tax_total_charge']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['tax_total_charge']['currency']);

        $houseConsignment->addChild('ram:TotalDisbursementPrepaidIndicator', $data['master_consignment']['house_consignment']['total_disbursement_prepaid'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:AgentTotalDisbursementAmount', $data['master_consignment']['house_consignment']['agent_total_disbursement']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['agent_total_disbursement']['currency']);

        $houseConsignment->addChild('ram:CarrierTotalDisbursementAmount', $data['master_consignment']['house_consignment']['carrier_total_disbursement']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['carrier_total_disbursement']['currency']);

        $houseConsignment->addChild('ram:TotalPrepaidChargeAmount', $data['master_consignment']['house_consignment']['total_prepaid']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['total_prepaid']['currency']);

        $houseConsignment->addChild('ram:TotalCollectChargeAmount', $data['master_consignment']['house_consignment']['total_collect']['amount'], 'iata:datamodel:3')
            ->addAttribute('currencyID', $data['master_consignment']['house_consignment']['total_collect']['currency']);

        $houseConsignment->addChild('ram:IncludedTareGrossWeightMeasure', $data['master_consignment']['house_consignment']['house_gross_weight']['value'], 'iata:datamodel:3')
            ->addAttribute('unitCode', $data['master_consignment']['house_consignment']['house_gross_weight']['unit_code']);

        // Demais campos
        $houseConsignment->addChild('ram:PackageQuantity', $data['master_consignment']['house_consignment']['package_quantity'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:TotalPieceQuantity', $data['master_consignment']['house_consignment']['total_piece_quantity'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:SummaryDescription', $data['master_consignment']['house_consignment']['summary_description'], 'iata:datamodel:3');
        $houseConsignment->addChild('ram:FreightRateTypeCode', $data['master_consignment']['house_consignment']['freight_rate_type'], 'iata:datamodel:3');

        // ConsignorParty
        $consignorParty = $houseConsignment->addChild('ram:ConsignorParty', null, 'iata:datamodel:3');
        $consignorParty->addChild('ram:Name', $data['master_consignment']['house_consignment']['consignor']['name'], 'iata:datamodel:3');

        $postalAddress = $consignorParty->addChild('ram:PostalStructuredAddress', null, 'iata:datamodel:3');
        $postalAddress->addChild('ram:PostcodeCode', $data['master_consignment']['house_consignment']['consignor']['address']['postcode'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:StreetName', $data['master_consignment']['house_consignment']['consignor']['address']['street'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CityName', $data['master_consignment']['house_consignment']['consignor']['address']['city'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CountryID', $data['master_consignment']['house_consignment']['consignor']['address']['country'], 'iata:datamodel:3');

        $tradeContact = $consignorParty->addChild('ram:DefinedTradeContact', null, 'iata:datamodel:3');
        $tradeContact->addChild('ram:DirectTelephoneCommunication', null, 'iata:datamodel:3')
            ->addChild('ram:CompleteNumber', $data['master_consignment']['house_consignment']['consignor']['contact']['phone'], 'iata:datamodel:3');

        // ConsigneeParty
        $consigneeParty = $houseConsignment->addChild('ram:ConsigneeParty', null, 'iata:datamodel:3');
        $consigneeParty->addChild('ram:Name', $data['master_consignment']['house_consignment']['consignee']['name'], 'iata:datamodel:3');

        $postalAddress = $consigneeParty->addChild('ram:PostalStructuredAddress', null, 'iata:datamodel:3');
        $postalAddress->addChild('ram:PostcodeCode', $data['master_consignment']['house_consignment']['consignee']['address']['postcode'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:StreetName', $data['master_consignment']['house_consignment']['consignee']['address']['street'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CityName', $data['master_consignment']['house_consignment']['consignee']['address']['city'], 'iata:datamodel:3');
        $postalAddress->addChild('ram:CountryID', $data['master_consignment']['house_consignment']['consignee']['address']['country'], 'iata:datamodel:3');

        $tradeContact = $consigneeParty->addChild('ram:DefinedTradeContact', null, 'iata:datamodel:3');
        $tradeContact->addChild('ram:DirectTelephoneCommunication', null, 'iata:datamodel:3')
            ->addChild('ram:CompleteNumber', $data['master_consignment']['house_consignment']['consignee']['contact']['phone'], 'iata:datamodel:3');

        // ApplicableTransportCargoInsurance
        $houseConsignment->addChild('ram:ApplicableTransportCargoInsurance', null, 'iata:datamodel:3')
            ->addChild('ram:CoverageInsuranceParty', null, 'iata:datamodel:3');

        // OriginLocation (dentro de IncludedHouseConsignment)
        $originLocation = $houseConsignment->addChild('ram:OriginLocation', null, 'iata:datamodel:3');
        $originLocation->addChild('ram:ID', $data['master_consignment']['origin_location']['id'], 'iata:datamodel:3');
        $originLocation->addChild('ram:Name', $data['master_consignment']['origin_location']['name'], 'iata:datamodel:3');

        // FinalDestinationLocation (dentro de IncludedHouseConsignment)
        $finalDestination = $houseConsignment->addChild('ram:FinalDestinationLocation', null, 'iata:datamodel:3');
        $finalDestination->addChild('ram:ID', $data['master_consignment']['final_destination']['id'], 'iata:datamodel:3');
        $finalDestination->addChild('ram:Name', $data['master_consignment']['final_destination']['name'], 'iata:datamodel:3');

        // Customs Notes
        foreach ($data['master_consignment']['house_consignment']['customs_notes'] as $note) {
            $customsNote = $houseConsignment->addChild('ram:IncludedCustomsNote', null, 'iata:datamodel:3');
            $customsNote->addChild('ram:ContentCode', $note['content_code'], 'iata:datamodel:3');
            $customsNote->addChild('ram:Content', $note['content'], 'iata:datamodel:3');
            $customsNote->addChild('ram:SubjectCode', $note['subject_code'], 'iata:datamodel:3');
            $customsNote->addChild('ram:CountryID', $note['country'], 'iata:datamodel:3');
        }

        // Consignment Items
        foreach ($data['master_consignment']['house_consignment']['items'] as $item) {
            $consignmentItem = $houseConsignment->addChild('ram:IncludedHouseConsignmentItem', null, 'iata:datamodel:3');
            $consignmentItem->addChild('ram:SequenceNumeric', $item['sequence'], 'iata:datamodel:3');

            $typeCode = $consignmentItem->addChild('ram:TypeCode', $item['type_code']['value'], 'iata:datamodel:3');
            $typeCode->addAttribute('listAgencyID', $item['type_code']['list_agency']);

            $consignmentItem->addChild('ram:GrossWeightMeasure', $item['gross_weight']['value'], 'iata:datamodel:3')
                ->addAttribute('unitCode', $item['gross_weight']['unit_code']);
            $consignmentItem->addChild('ram:GrossVolumeMeasure', $item['gross_volume']['value'], 'iata:datamodel:3')
                ->addAttribute('unitCode', $item['gross_volume']['unit_code']);
            $consignmentItem->addChild('ram:PackageQuantity', $item['package_quantity'], 'iata:datamodel:3');
            $consignmentItem->addChild('ram:PieceQuantity', $item['piece_quantity'], 'iata:datamodel:3');

            $natureIdentification = $consignmentItem->addChild('ram:NatureIdentificationTransportCargo', null, 'iata:datamodel:3');
            $natureIdentification->addChild('ram:Identification', $item['description'], 'iata:datamodel:3');

            $freightRate = $consignmentItem->addChild('ram:ApplicableFreightRateServiceCharge', null, 'iata:datamodel:3');
            $freightRate->addChild('ram:ChargeableWeightMeasure', $item['freight_rate']['chargeable_weight']['value'], 'iata:datamodel:3')
                ->addAttribute('unitCode', $item['freight_rate']['chargeable_weight']['unit_code']);
            $freightRate->addChild('ram:AppliedAmount', $item['freight_rate']['applied_amount']['value'], 'iata:datamodel:3')
                ->addAttribute('currencyID', $item['freight_rate']['applied_amount']['currency']);
        }

        // Formatar o XML para saída
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }
};
