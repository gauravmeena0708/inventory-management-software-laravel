<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tasks = array(
            array(
                "id" => 1,
                "name" => "CISCO Switches Warranty Renewal and New Product purchase",
                "Status" => "PutUp"
            ),
            array(
                "id" => 2,
                "name" => "Servers warranty renewal and Purchase",
                "Status" => "PutUp"
            ),
            array(
                "id" => 3,
                "name" => "Payment of WIPRO/NICSI",
                "Status" => "PutUp"
            ),
            array(
                "id" => 4,
                "name" => "GNOC Manpower Agency Renewal/Retendering",
                "Status" => "PutUp"
            ),
            array(
                "id" => 5,
                "name" => "Purchasing of Laptop/Desktop through GEM",
                "Status" => "PutUp"
            ),
            array(
                "id" => 6,
                "name" => "Procurement of EMS- Backup and Archival Solution",
                "Status" => "PutUp"
            ),
            array(
                "id" => 7,
                "name" => "Payment TCIL",
                "Status" => "PutUp"
            ),
            array(
                "id" => 8,
                "name" => "ACS ATS Oracle extension",
                "Status" => "PutUp"
            ),
            array(
                "id" => 9,
                "name" => "Payment UTIITSL",
                "Status" => "PutUp"
            ),
            array(
                "id" => 10,
                "name" => "Renewal / extension of Nessus Tools",
                "Status" => "PutUp"
            ),
            array(
                "id" => 11,
                "name" => "renewal / Extension / tender process for AMC of UPS",
                "Status" => "PutUp"
            ),
            array(
                "id" => 12,
                "name" => "Information security policy",
                "Status" => "PutUp"
            ),
            array(
                "id" => 13,
                "name" => "TRAI SMS related issue",
                "Status" => "PutUp"
            ),
            array(
                "id" => 14,
                "name" => "Additional work by CDAC",
                "Status" => "PutUp"
            ),
            array(
                "id" => 15,
                "name" => "Aadhaar Vault and encryption",
                "Status" => "PutUp"
            ),
            array(
                "id" => 16,
                "name" => "E-Office- sparrow- EPFIGMS- HRM - cost estimation",
                "Status" => "PutUp"
            ),
            array(
                "id" => 17,
                "name" => "E-Sign Demographic file",
                "Status" => "PutUp"
            ),
            array(
                "id" => 18,
                "name" => "UAN -ASA /CSC file PutUp for finance",
                "Status" => "PutUp"
            ),
            array(
                "id" => 19,
                "name" => "Engagement of Security Audit Agency for hardware & Software security audit",
                "Status" => "PutUp"
            ),
            array(
                "id" => 20,
                "name" => "Link order of DD(IS)",
                "Status" => "PutUp"
            ),
            array(
                "id" => 21,
                "name" => "Requirement of CSD for dialer headsets",
                "Status" => "PutUp"
            ),
            array(
                "id" => 22,
                "name" => "Developer requirements (EpfiGMS- CAIU- Patnaik sir)",
                "Status" => "PutUp"
            ),
            array(
                "id" => 23,
                "name" => "Transfer Applications of officials of NDC",
                "Status" => "PutUp"
            ),
            array(
                "id" => 24,
                "name" => "Mail to NIC for obsolence",
                "Status" => "PutUp"
            ),
            array(
                "id" => 25,
                "name" => "C-DAC maintenance agreement 2020 & 2021 Agenda EC",
                "Status" => "ToDo"
            ),
            array(
                "id" => 26,
                "name" => "Absentee issue of DD(IS). Smt Shanthi Sivaram- Smt. Sathyabama",
                "Status" => "ToDo"
            ),
            array(
                "id" => 27,
                "name" => "Withdraw DPA Sh. Amit Chauhan",
                "Status" => "ToDo"
            ),
            array(
                "id" => 28,
                "name" => "EC-CBT Agendas",
                "Status" => "ToDo"
            ),
            array(
                "id" => 29,
                "name" => "AC/TV purchase for conference room",
                "Status" => "ToDo"
            ),
            array(
                "id" => 30,
                "name" => "CBI letter Replies",
                "Status" => "ToDo"
            ),
            array(
                "id" => 31,
                "name" => "Railtel 50MBPS CSD agreement and extension",
                "Status" => "completed"
            ),
            array(
                "id" => 32,
                "name" => "AMC of PAC",
                "Status" => "completed"
            ),
            array(
                "id" => 33,
                "name" => "Sept Payment of Dry cleaning bills",
                "Status" => "completed"
            ),
            array(
                "id" => 34,
                "name" => "Nov-Attendance of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 35,
                "name" => "Nov-Payment of Washroom Hygiene",
                "Status" => "completed"
            ),
            array(
                "id" => 36,
                "name" => "Oct- Payment of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 37,
                "name" => "Oct- Payment of Rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 38,
                "name" => "Nov-Payment of Security Guard",
                "Status" => "completed"
            ),
            array(
                "id" => 39,
                "name" => "Nov-Payment of MTS",
                "Status" => "completed"
            ),
            array(
                "id" => 40,
                "name" => "Nov-Payment of Toll free Number",
                "Status" => "completed"
            ),
            array(
                "id" => 41,
                "name" => "Nov-Payment of Data Cards",
                "Status" => "completed"
            ),
            array(
                "id" => 42,
                "name" => "Nov-Payment of Call lines Number",
                "Status" => "completed"
            ),
            array(
                "id" => 43,
                "name" => "Nov-DO letter to CPFC - PutUp",
                "Status" => "completed"
            ),
            array(
                "id" => 44,
                "name" => "Nov-Payment of Dry cleaning bills",
                "Status" => "completed"
            ),
            array(
                "id" => 45,
                "name" => "Nov-Payment of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 46,
                "name" => "Nov-Attendance of PAs to be sent to ASD",
                "Status" => "completed"
            ),
            array(
                "id" => 47,
                "name" => "Nov-Attendance of MTS",
                "Status" => "completed"
            ),
            array(
                "id" => 48,
                "name" => "Nov-Attendance of Security Gurard",
                "Status" => "completed"
            ),
            array(
                "id" => 49,
                "name" => "C-DAC Maintenance Agreement 2021",
                "Status" => "completed"
            ),
            array(
                "id" => 50,
                "name" => "Certificate SSL of Website (Mail of Sh. Mohd. Qamar)",
                "Status" => "completed"
            ),
            array(
                "id" => 51,
                "name" => "Renewal of RHEL Subscription",
                "Status" => "completed"
            ),
            array(
                "id" => 52,
                "name" => "Laptop file for ADs- DPA- APFC- RPFC-II- SO",
                "Status" => "completed"
            ),
            array(
                "id" => 53,
                "name" => "Sept Payment of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 54,
                "name" => "Oct- Payment of Data Cards",
                "Status" => "completed"
            ),
            array(
                "id" => 55,
                "name" => "Oct- Payment of Call lines Number",
                "Status" => "completed"
            ),
            array(
                "id" => 56,
                "name" => "Oct- DO letter to CPFC - PutUp",
                "Status" => "completed"
            ),
            array(
                "id" => 57,
                "name" => "Sept Payment of Rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 58,
                "name" => "File related to VRS of Sh. Mohd Qamar",
                "Status" => "completed"
            ),
            array(
                "id" => 59,
                "name" => "Annual report 2020-21 MOL&E",
                "Status" => "completed"
            ),
            array(
                "id" => 60,
                "name" => "Oct- Payment of MTS",
                "Status" => "completed"
            ),
            array(
                "id" => 61,
                "name" => "Oct- Payment of Toll free Number",
                "Status" => "completed"
            ),
            array(
                "id" => 62,
                "name" => "Oct- Attendance of PAs to be sent to ASD",
                "Status" => "completed"
            ),
            array(
                "id" => 63,
                "name" => "Oct- Attendance of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 64,
                "name" => "ASO file",
                "Status" => "completed"
            ),
            array(
                "id" => 65,
                "name" => "DEO file",
                "Status" => "completed"
            ),
            array(
                "id" => 66,
                "name" => "SMS invoice based on exempted rates",
                "Status" => "completed"
            ),
            array(
                "id" => 67,
                "name" => "Vigilance awareness week - information to VCC.vig",
                "Status" => "completed"
            ),
            array(
                "id" => 68,
                "name" => "5 more RTIs",
                "Status" => "completed"
            ),
            array(
                "id" => 69,
                "name" => "Addtional Storage procurement - C-DAC reply forwarded to committee",
                "Status" => "completed"
            ),
            array(
                "id" => 70,
                "name" => "Annual report 2018-19",
                "Status" => "completed"
            ),
            array(
                "id" => 71,
                "name" => "April - Payment Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 72,
                "name" => "April - Payment of Datacard",
                "Status" => "completed"
            ),
            array(
                "id" => 73,
                "name" => "April - Payment of rented desktop",
                "Status" => "completed"
            ),
            array(
                "id" => 74,
                "name" => "ASO Syllabus PutUp/NATRSS Training Mail",
                "Status" => "completed"
            ),
            array(
                "id" => 75,
                "name" => "ATR for CBT",
                "Status" => "completed"
            ),
            array(
                "id" => 76,
                "name" => "ATR fro EC- CBT",
                "Status" => "completed"
            ),
            array(
                "id" => 77,
                "name" => "Attendence approval",
                "Status" => "completed"
            ),
            array(
                "id" => 78,
                "name" => "Budget allocation to field office application",
                "Status" => "completed"
            ),
            array(
                "id" => 79,
                "name" => "CDAC esign Agreement to be signed 2019-20",
                "Status" => "completed"
            ),
            array(
                "id" => 80,
                "name" => "certificate of apt services",
                "Status" => "completed"
            ),
            array(
                "id" => 81,
                "name" => "Changing of Datacard plan to 499/per month - 100GB per month",
                "Status" => "completed"
            ),
            array(
                "id" => 82,
                "name" => "E-sign contract File",
                "Status" => "completed"
            ),
            array(
                "id" => 83,
                "name" => "EPFiGMS",
                "Status" => "completed"
            ),
            array(
                "id" => 84,
                "name" => "Extension letter for PGCIL- RAILTEL- BSNL",
                "Status" => "completed"
            ),
            array(
                "id" => 85,
                "name" => "extension of agreement of rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 86,
                "name" => "Extension of contract for housekeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 87,
                "name" => "Extension of contract of Helpdesk DEOs",
                "Status" => "completed"
            ),
            array(
                "id" => 88,
                "name" => "Extension of contract of rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 89,
                "name" => "Fixed Asset",
                "Status" => "completed"
            ),
            array(
                "id" => 90,
                "name" => "Forward to PDU - Housekeeping staff procurement request",
                "Status" => "completed"
            ),
            array(
                "id" => 91,
                "name" => "July Attendance of Devlopers",
                "Status" => "completed"
            ),
            array(
                "id" => 92,
                "name" => "July Attendance of Houskeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 93,
                "name" => "July Attendance of Security Guards",
                "Status" => "completed"
            ),
            array(
                "id" => 94,
                "name" => "July Attendence to be sent to ASD for PAs",
                "Status" => "completed"
            ),
            array(
                "id" => 95,
                "name" => "July DO letter to CPFC",
                "Status" => "completed"
            ),
            array(
                "id" => 96,
                "name" => "July Payment of Call lines Number",
                "Status" => "completed"
            ),
            array(
                "id" => 97,
                "name" => "July Payment of Data Cards",
                "Status" => "completed"
            ),
            array(
                "id" => 98,
                "name" => "July Payment of Gas Bill",
                "Status" => "completed"
            ),
            array(
                "id" => 99,
                "name" => "July Payment of Housekeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 100,
                "name" => "July Payment of Security Staff",
                "Status" => "completed"
            ),
            array(
                "id" => 101,
                "name" => "July Payment of Toll free Number",
                "Status" => "completed"
            ),
            array(
                "id" => 102,
                "name" => "June Attendance of Devlopers",
                "Status" => "completed"
            ),
            array(
                "id" => 103,
                "name" => "June Attendance of Houskeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 104,
                "name" => "June Attendence to be sent to ASD for PAs",
                "Status" => "completed"
            ),
            array(
                "id" => 105,
                "name" => "June DO letter to CPFC - PutUp",
                "Status" => "completed"
            ),
            array(
                "id" => 106,
                "name" => "June Payment of Call lines Number",
                "Status" => "completed"
            ),
            array(
                "id" => 107,
                "name" => "June Payment of Data Cards",
                "Status" => "completed"
            ),
            array(
                "id" => 108,
                "name" => "June Payment of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 109,
                "name" => "June Payment of electricity bill",
                "Status" => "completed"
            ),
            array(
                "id" => 110,
                "name" => "June Payment of Gas Bill",
                "Status" => "completed"
            ),
            array(
                "id" => 111,
                "name" => "June Payment of Housekeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 112,
                "name" => "June Payment of Rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 113,
                "name" => "June Payment of Security Staff",
                "Status" => "completed"
            ),
            array(
                "id" => 114,
                "name" => "June Payment of Toll free Number",
                "Status" => "completed"
            ),
            array(
                "id" => 115,
                "name" => "List of Pending Payment",
                "Status" => "completed"
            ),
            array(
                "id" => 116,
                "name" => "Major achievements in last year",
                "Status" => "completed"
            ),
            array(
                "id" => 117,
                "name" => "May Payment of Call lines Number",
                "Status" => "completed"
            ),
            array(
                "id" => 118,
                "name" => "May-Attendance of Devlopers",
                "Status" => "completed"
            ),
            array(
                "id" => 119,
                "name" => "May-Attendance of Houskeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 120,
                "name" => "May-Attendance of PAs",
                "Status" => "completed"
            ),
            array(
                "id" => 121,
                "name" => "May-Attendence to be sent to ASD for PAs",
                "Status" => "completed"
            ),
            array(
                "id" => 122,
                "name" => "May-DO letter to CPFC",
                "Status" => "completed"
            ),
            array(
                "id" => 123,
                "name" => "May-Payment of Data Cards",
                "Status" => "completed"
            ),
            array(
                "id" => 124,
                "name" => "May-Payment of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 125,
                "name" => "May-Payment of Housekeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 126,
                "name" => "May-Payment of Rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 127,
                "name" => "May-Payment of Security Staff",
                "Status" => "completed"
            ),
            array(
                "id" => 128,
                "name" => "May-Payment of Toll free Number",
                "Status" => "completed"
            ),
            array(
                "id" => 129,
                "name" => "May-Payment whose PVR submitted Collabera",
                "Status" => "completed"
            ),
            array(
                "id" => 130,
                "name" => "MPLS work order - PGCIL PutUp- BSNL and Railtel",
                "Status" => "completed"
            ),
            array(
                "id" => 131,
                "name" => "Notice to officers for leaving station without permission",
                "Status" => "completed"
            ),
            array(
                "id" => 132,
                "name" => "OM related to arogya setu app with email (sent in email)",
                "Status" => "completed"
            ),
            array(
                "id" => 133,
                "name" => "OM related to payment of wages (email)",
                "Status" => "completed"
            ),
            array(
                "id" => 134,
                "name" => "Payment BSNL (for period after June 2019)",
                "Status" => "completed"
            ),
            array(
                "id" => 135,
                "name" => "Payment Collaberra feb-march",
                "Status" => "completed"
            ),
            array(
                "id" => 136,
                "name" => "Payment of Telephone bills MTNL",
                "Status" => "completed"
            ),
            array(
                "id" => 137,
                "name" => "Payment PGCIL MPLS-ILL",
                "Status" => "completed"
            ),
            array(
                "id" => 138,
                "name" => "Payment railtel e-offiice",
                "Status" => "completed"
            ),
            array(
                "id" => 139,
                "name" => "Payment Railtel for EPFiGMS",
                "Status" => "completed"
            ),
            array(
                "id" => 140,
                "name" => "Payment Railtel MPLS-ILL",
                "Status" => "completed"
            ),
            array(
                "id" => 141,
                "name" => "Payment SMS auth and otp",
                "Status" => "completed"
            ),
            array(
                "id" => 142,
                "name" => "Payment Squad 7 April",
                "Status" => "completed"
            ),
            array(
                "id" => 143,
                "name" => "Payment to C-DAC for Study conducted",
                "Status" => "completed"
            ),
            array(
                "id" => 144,
                "name" => "Payment to Railtel for epfigms",
                "Status" => "completed"
            ),
            array(
                "id" => 145,
                "name" => "Payment Virtuous April",
                "Status" => "completed"
            ),
            array(
                "id" => 146,
                "name" => "Payment World Class April",
                "Status" => "completed"
            ),
            array(
                "id" => 147,
                "name" => "PM award Writeup",
                "Status" => "completed"
            ),
            array(
                "id" => 148,
                "name" => "Proposal for webex purchase 1 host and about 1000 users",
                "Status" => "completed"
            ),
            array(
                "id" => 149,
                "name" => "Purchase of Fans",
                "Status" => "completed"
            ),
            array(
                "id" => 150,
                "name" => "Purchase of for portable Projector for RPFC-I(IS)",
                "Status" => "completed"
            ),
            array(
                "id" => 151,
                "name" => "Release of payment for technical manpower for Jan to March 2020to 3i Infotech",
                "Status" => "completed"
            ),
            array(
                "id" => 152,
                "name" => "Reminder to C-DAC regarding SIEM/DDOS",
                "Status" => "completed"
            ),
            array(
                "id" => 153,
                "name" => "Reply of RTI forwarded by Bangalore",
                "Status" => "completed"
            ),
            array(
                "id" => 154,
                "name" => "RTI of Madhukar B rao (email)",
                "Status" => "completed"
            ),
            array(
                "id" => 155,
                "name" => "Sharing of downtime detail with RAILTAIL- BSNL- PGCIL",
                "Status" => "completed"
            ),
            array(
                "id" => 156,
                "name" => "SMS Noting by Vineet Gupta",
                "Status" => "completed"
            ),
            array(
                "id" => 157,
                "name" => "Surrendering of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 158,
                "name" => "unused Datacard closure",
                "Status" => "completed"
            ),
            array(
                "id" => 159,
                "name" => "Work done by each team",
                "Status" => "completed"
            ),
            array(
                "id" => 160,
                "name" => "status of Inquiry/Investigation before police/CBI in NDC fraud -reg (email)",
                "Status" => "completed"
            ),
            array(
                "id" => 161,
                "name" => "August DO letter to CPFC - PutUp",
                "Status" => "completed"
            ),
            array(
                "id" => 162,
                "name" => "August Payment of Data Cards",
                "Status" => "completed"
            ),
            array(
                "id" => 163,
                "name" => "Sept Attendance of PAs to be sent to ASD",
                "Status" => "completed"
            ),
            array(
                "id" => 164,
                "name" => "Sept Payment of MTS",
                "Status" => "completed"
            ),
            array(
                "id" => 165,
                "name" => "Sept Payment of Data Cards",
                "Status" => "completed"
            ),
            array(
                "id" => 166,
                "name" => "Sept DO letter to CPFC - PutUp",
                "Status" => "completed"
            ),
            array(
                "id" => 167,
                "name" => "Purchase of heavy-duty scanner",
                "Status" => "completed"
            ),
            array(
                "id" => 168,
                "name" => "July Payment of Rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 169,
                "name" => "Purchase of 3 paper shredders",
                "Status" => "completed"
            ),
            array(
                "id" => 170,
                "name" => "August Attendence to be sent to ASD for PAs",
                "Status" => "completed"
            ),
            array(
                "id" => 171,
                "name" => "Sept Attendance of MTS",
                "Status" => "completed"
            ),
            array(
                "id" => 172,
                "name" => "Sept Attendance of Security Gurard",
                "Status" => "completed"
            ),
            array(
                "id" => 173,
                "name" => "CBI related information part 1",
                "Status" => "completed"
            ),
            array(
                "id" => 174,
                "name" => "Job description",
                "Status" => "completed"
            ),
            array(
                "id" => 175,
                "name" => "August Attendance of Devlopers",
                "Status" => "completed"
            ),
            array(
                "id" => 176,
                "name" => "August Payment of Housekeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 177,
                "name" => "August Payment of Security Staff",
                "Status" => "completed"
            ),
            array(
                "id" => 178,
                "name" => "August Attendance of Houskeeping",
                "Status" => "completed"
            ),
            array(
                "id" => 179,
                "name" => "July Payment of electricity bill",
                "Status" => "completed"
            ),
            array(
                "id" => 180,
                "name" => "August Attendance of security Guards",
                "Status" => "completed"
            ),
            array(
                "id" => 181,
                "name" => "August Payment of Washroom Hygiene",
                "Status" => "completed"
            ),
            array(
                "id" => 182,
                "name" => "August Payment of Dry cleaning",
                "Status" => "completed"
            ),
            array(
                "id" => 183,
                "name" => "CBI related information part 2",
                "Status" => "completed"
            ),
            array(
                "id" => 184,
                "name" => "Initiative PDUNASS",
                "Status" => "completed"
            ),
            array(
                "id" => 185,
                "name" => "Common IP Pool Payment (NIXI)",
                "Status" => "completed"
            ),
            array(
                "id" => 186,
                "name" => "Laptop for staff posted at NDC",
                "Status" => "completed"
            ),
            array(
                "id" => 187,
                "name" => "Redhat Mandays - attach past usage of man days + work order",
                "Status" => "completed"
            ),
            array(
                "id" => 188,
                "name" => "Release of EMD ammount (Manpower related file) PBG or encashing of the same",
                "Status" => "completed"
            ),
            array(
                "id" => 189,
                "name" => "Sept Payment of Security Guard",
                "Status" => "completed"
            ),
            array(
                "id" => 190,
                "name" => "Sept Attendance of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 191,
                "name" => "August Payment of Call lines Number",
                "Status" => "completed"
            ),
            array(
                "id" => 192,
                "name" => "August Payment of Rented Desktops",
                "Status" => "completed"
            ),
            array(
                "id" => 193,
                "name" => "Memo for slowness",
                "Status" => "completed"
            ),
            array(
                "id" => 194,
                "name" => "August Payment of electricity bill",
                "Status" => "completed"
            ),
            array(
                "id" => 195,
                "name" => "August Payment of Toll free Number",
                "Status" => "completed"
            ),
            array(
                "id" => 196,
                "name" => "Oct- Attendance of Security Gurard",
                "Status" => "completed"
            ),
            array(
                "id" => 197,
                "name" => "Sept Payment of electricity bill",
                "Status" => "completed"
            ),
            array(
                "id" => 198,
                "name" => "Sept Payment of Toll free Number",
                "Status" => "completed"
            ),
            array(
                "id" => 199,
                "name" => "Oct- Attendance of MTS",
                "Status" => "completed"
            ),
            array(
                "id" => 200,
                "name" => "Oct- Payment of Security Guard",
                "Status" => "completed"
            ),
            array(
                "id" => 201,
                "name" => "C-DAC E-Sign Agreement 2021",
                "Status" => "completed"
            ),
            array(
                "id" => 202,
                "name" => "Certificate SSL for epfiGMS for one or Two years",
                "Status" => "completed"
            ),
            array(
                "id" => 203,
                "name" => "August Payment of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 204,
                "name" => "July Payment of Developers",
                "Status" => "completed"
            ),
            array(
                "id" => 205,
                "name" => "Certificate Digital signing Unified portal",
                "Status" => "completed"
            ),
        );
        Task::insert($tasks);
    }
}
