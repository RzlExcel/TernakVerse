<?php

namespace App\Services;

class IsoMetadataGeneratorService
{
    /**
     * Menghasilkan dokumen XML standar ISO 19115:2003 / ISO 19139
     * untuk interoperabilitas dengan Geoportal Kabupaten Banyumas & Ina-Geoportal BIG.
     */
    public function generateIso19115Xml(string $datasetType = 'PETERNAKAN'): string
    {
        $fileId = $datasetType === 'PENYAKIT'
            ? 'ID-DINPERTAN-BMS-TERNAKVERSE-EWS-2026'
            : 'ID-DINPERTAN-BMS-TERNAKVERSE-2026';

        $title = $datasetType === 'PENYAKIT'
            ? 'Data Geospasial Sebaran Kasus Penyakit Hewan Menular (PMK & LSD) Kabupaten Banyumas'
            : 'Data Geospasial Populasi, Produksi Ternak, dan Rumah Potong Hewan Kabupaten Banyumas';

        $abstract = $datasetType === 'PENYAKIT'
            ? 'Dataset geospasial tematik kejadian dan zonasi risiko penyakit hewan menular (Penyakit Mulut dan Kuku / PMK dan Lumpy Skin Disease / LSD) tingkat kecamatan di Kabupaten Banyumas mengacu standar KUGI unsur ADMINISTRASI_AR_PENYAKITHEWAN.'
            : 'Dataset geospasial tematik komoditas peternakan (sapi potong, sapi perah, kambing, domba, unggas), produksi peternakan, dan sebaran fasilitas Rumah Potong Hewan (RPH) di 27 kecamatan Kabupaten Banyumas mengacu standar KUGI unsur ADMINISTRASI_AR_PETERNAKAN.';

        $dateStamp = date('Y-m-d');
        $pubDate = '2026-09-30';

        // Bounding Box Geospasial Kabupaten Banyumas
        $west  = '108.8893';
        $east  = '109.4464';
        $south = '-7.6658';
        $north = '-7.2463';

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd"
                 xmlns:gco="http://www.isotc211.org/2005/gco"
                 xmlns:gml="http://www.opengis.net/gml"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20070417/gmd/gmd.xsd">
    <gmd:fileIdentifier>
        <gco:CharacterString>{$fileId}</gco:CharacterString>
    </gmd:fileIdentifier>
    <gmd:language>
        <gco:CharacterString>ind</gco:CharacterString>
    </gmd:language>
    <gmd:characterSet>
        <gmd:MD_CharacterSetCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_CharacterSetCode" codeListValue="utf8">utf8</gmd:MD_CharacterSetCode>
    </gmd:characterSet>
    <gmd:hierarchyLevel>
        <gmd:MD_ScopeCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#MD_ScopeCode" codeListValue="dataset">dataset</gmd:MD_ScopeCode>
    </gmd:hierarchyLevel>
    <gmd:contact>
        <gmd:CI_ResponsibleParty>
            <gmd:organisationName>
                <gco:CharacterString>Dinas Pertanian dan Ketahanan Pangan Kabupaten Banyumas</gco:CharacterString>
            </gmd:organisationName>
            <gmd:positionName>
                <gco:CharacterString>Bidang Peternakan, Kesehatan Hewan dan Kesmavet</gco:CharacterString>
            </gmd:positionName>
            <gmd:contactInfo>
                <gmd:CI_Contact>
                    <gmd:address>
                        <gmd:CI_Address>
                            <gmd:deliveryPoint>
                                <gco:CharacterString>Jl. Prof. Dr. Soeharso (Komplek GOR Satria) No. 45 Purwokerto</gco:CharacterString>
                            </gmd:deliveryPoint>
                            <gmd:city>
                                <gco:CharacterString>Banyumas</gco:CharacterString>
                            </gmd:city>
                            <gmd:postalCode>
                                <gco:CharacterString>53114</gco:CharacterString>
                            </gmd:postalCode>
                            <gmd:country>
                                <gco:CharacterString>Indonesia</gco:CharacterString>
                            </gmd:country>
                            <gmd:electronicMailAddress>
                                <gco:CharacterString>dinpertankp@banyumaskab.go.id</gco:CharacterString>
                            </gmd:electronicMailAddress>
                        </gmd:CI_Address>
                    </gmd:address>
                </gmd:CI_Contact>
            </gmd:contactInfo>
            <gmd:role>
                <gmd:CI_RoleCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#CI_RoleCode" codeListValue="pointOfContact">pointOfContact</gmd:CI_RoleCode>
            </gmd:role>
        </gmd:CI_ResponsibleParty>
    </gmd:contact>
    <gmd:dateStamp>
        <gco:Date>{$dateStamp}</gco:Date>
    </gmd:dateStamp>
    <gmd:metadataStandardName>
        <gco:CharacterString>ISO 19115:2003/Cor.1:2006 Geographic Information - Metadata</gco:CharacterString>
    </gmd:metadataStandardName>
    <gmd:metadataStandardVersion>
        <gco:CharacterString>KUGI 2.1 / Standar Badan Informasi Geospasial (BIG)</gco:CharacterString>
    </gmd:metadataStandardVersion>
    <gmd:identificationInfo>
        <gmd:MD_DataIdentification>
            <gmd:citation>
                <gmd:CI_Citation>
                    <gmd:title>
                        <gco:CharacterString>{$title}</gco:CharacterString>
                    </gmd:title>
                    <gmd:date>
                        <gmd:CI_Date>
                            <gmd:date>
                                <gco:Date>{$pubDate}</gco:Date>
                            </gmd:date>
                            <gmd:dateType>
                                <gmd:CI_DateTypeCode codeList="http://standards.iso.org/ittf/PubliclyAvailableStandards/ISO_19139_Schemas/resources/codelist/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication">publication</gmd:CI_DateTypeCode>
                            </gmd:dateType>
                        </gmd:CI_Date>
                    </gmd:date>
                </gmd:CI_Citation>
            </gmd:citation>
            <gmd:abstract>
                <gco:CharacterString>{$abstract}</gco:CharacterString>
            </gmd:abstract>
            <gmd:descriptiveKeywords>
                <gmd:MD_Keywords>
                    <gmd:keyword><gco:CharacterString>Peternakan</gco:CharacterString></gmd:keyword>
                    <gmd:keyword><gco:CharacterString>Kabupaten Banyumas</gco:CharacterString></gmd:keyword>
                    <gmd:keyword><gco:CharacterString>Kesehatan Hewan</gco:CharacterString></gmd:keyword>
                    <gmd:keyword><gco:CharacterString>WebGIS</gco:CharacterString></gmd:keyword>
                    <gmd:keyword><gco:CharacterString>KUGI</gco:CharacterString></gmd:keyword>
                </gmd:MD_Keywords>
            </gmd:descriptiveKeywords>
            <gmd:extent>
                <gmd:EX_Extent>
                    <gmd:geographicElement>
                        <gmd:EX_GeographicBoundingBox>
                            <gmd:westBoundLongitude><gco:Decimal>{$west}</gco:Decimal></gmd:westBoundLongitude>
                            <gmd:eastBoundLongitude><gco:Decimal>{$east}</gco:Decimal></gmd:eastBoundLongitude>
                            <gmd:southBoundLatitude><gco:Decimal>{$south}</gco:Decimal></gmd:southBoundLatitude>
                            <gmd:northBoundLatitude><gco:Decimal>{$north}</gco:Decimal></gmd:northBoundLatitude>
                        </gmd:EX_GeographicBoundingBox>
                    </gmd:geographicElement>
                </gmd:EX_Extent>
            </gmd:extent>
        </gmd:MD_DataIdentification>
    </gmd:identificationInfo>
    <gmd:referenceSystemInfo>
        <gmd:MD_ReferenceSystem>
            <gmd:referenceSystemIdentifier>
                <gmd:RS_Identifier>
                    <gmd:code>
                        <gco:CharacterString>SRGI2013 / EPSG:4326 (WGS 84)</gco:CharacterString>
                    </gmd:code>
                </gmd:RS_Identifier>
            </gmd:referenceSystemIdentifier>
        </gmd:MD_ReferenceSystem>
    </gmd:referenceSystemInfo>
</gmd:MD_Metadata>
XML;

        return trim($xml);
    }
}
