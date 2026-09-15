<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CompanyDocumentType: string implements HasLabel
{
    case DeedOfEstablishment = 'deed_of_establishment';

    case DeedOfAmendment = 'deed_of_amendment';

    case BusinessLicense = 'business_license';

    case NpwpCertificate = 'npwp_certificate';

    case CompanyRegistration = 'company_registration';

    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::DeedOfEstablishment => 'Akta Pendirian',
            self::DeedOfAmendment => 'Akta Perubahan',
            self::BusinessLicense => 'NIB / Izin Usaha',
            self::NpwpCertificate => 'NPWP / Surat Keterangan Terdaftar',
            self::CompanyRegistration => 'TDP / Pendaftaran Perusahaan',
            self::Other => 'Lainnya',
        };
    }
}
