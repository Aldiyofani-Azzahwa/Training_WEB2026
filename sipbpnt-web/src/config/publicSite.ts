export interface PublicNavigationItem {
  label: string
  routeName:
    | 'home'
    | 'about-bpnt'
    | 'about-sipbpnt'
    | 'benefits'
    | 'faq'
    | 'contact'
}

export const publicNavigation:
  PublicNavigationItem[] = [
    {
      label: 'Beranda',
      routeName: 'home',
    },
    {
      label: 'Tentang BPNT',
      routeName: 'about-bpnt',
    },
    {
      label: 'Tentang SIPBPNT',
      routeName: 'about-sipbpnt',
    },
    {
      label: 'Manfaat',
      routeName: 'benefits',
    },
    {
      label: 'FAQ',
      routeName: 'faq',
    },
    {
      label: 'Kontak',
      routeName: 'contact',
    },
  ]

export const publicSite = {
  name: 'SIPBPNT',

  fullName:
    'Sistem Informasi Pendataan dan Monitoring BPNT',

  agency:
    'Dinas Sosial Kota Mojokerto',

  logoPath:
    '/branding/logo-sipbpnt.png',

  address:
    'Jl. Benteng Pancasila No. 25, Mergelo, Balongsari, Kecamatan Magersari, Kota Mojokerto, Jawa Timur 61314',

  telephone:
    '0321-396249',

  telephoneHref:
    'tel:+62321396249',

  email:
    'dinsos@mojokertokota.go.id',

  emailHref:
    'mailto:dinsos@mojokertokota.go.id',
} as const