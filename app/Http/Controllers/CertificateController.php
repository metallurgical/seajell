<?php
// Copyright (c) 2021 Muhammad Hanis Irfan bin Mohd Zaid

// This file is part of SeaJell.

// SeaJell is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// SeaJell is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with SeaJell.  If not, see <https://www.gnu.org/licenses/>.

namespace App\Http\Controllers;

use PDF;
use ZipArchive;
use TCPDF_FONTS;
use TCPDF_COLORS;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use App\Models\EventFont;
use App\Models\Certificate;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\CertificateViewActivity;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\MainController;
use App\Models\CertificateCollectionHistory;
use App\Models\CertificateCollectionDeletionSchedule;
use PhpOffice\PhpSpreadsheet\Reader\Exception as PHPSpreadsheetReaderException;

class CertificateController extends MainController
{
    public function authenticity(Request $request, $uid){
        if(Certificate::where('uid', $uid)->first()){
            $certificate = Certificate::select('users.fullname', 'certificates.uid', 'certificates.type', 'events.name', 'events.date', 'events.organiser_name', 'events.signature_first', 'events.signature_first_name', 'events.signature_first_position', 'events.signature_second', 'events.signature_second_name', 'events.signature_second_position', 'events.signature_third', 'events.signature_third_name', 'events.signature_third_position', 'certificates.position', 'certificates.category')->join('users', 'certificates.user_id', 'users.id')->join('events', 'certificates.event_id', 'events.id')->where('certificates.uid', $uid)->first();
            return view('certificate.authenticity')->with(['appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'systemSetting' => $this->systemSetting, 'certificate' => $certificate]);
        }else{
            abort(404, 'Sijil tidak dijumpai.');
        }
    }

    public function addCertificateView(Request $request){
        return view('certificate.add')->with(['appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'systemSetting' => $this->systemSetting]);
    }
    public function certificateListView(Request $request){
        $pagination = 15;
            // Check for filters and search
        if($request->filled('sort_by') AND $request->filled('sort_order') AND $request->has('search')){
            $sortBy = $request->sort_by;
            $sortOrder = $request->sort_order;
            $search = $request->search;
            if(!empty($search)){
                switch($search){
                    case 'penghargaan':
                        $search = 'appreciation';
                        break;
                    case 'pencapaian':
                        $search = 'achievement';
                        break;
                    case 'penyertaan':
                        $search = 'participation';
                        break;
                    default:
                        $search = $search;
                        break;
                }
                if(Gate::allows('authAdmin')){
                    $certificates = Certificate::join('users', 'certificates.user_id', 'users.id')->join('events', 'certificates.event_id', 'events.id')->select('certificates.uid', 'users.fullname', 'users.username','events.name', 'certificates.type', 'certificates.position', 'certificates.category')->where('certificates.uid', 'LIKE', "%{$search}%")->orWhere('users.fullname', 'LIKE', "%{$search}%")->orWhere('events.name', 'LIKE', "%{$search}%")->orWhere('certificates.type', 'LIKE', "%{$search}%")->orWhere('certificates.position', 'LIKE', "%{$search}%")->orWhere('certificates.category', 'LIKE', "%{$search}%")->orderBy($sortBy, $sortOrder)->paginate($pagination)->withQueryString();
                }else{
                    $certificates = Certificate::join('users', 'certificates.user_id', 'users.id')->join('events', 'certificates.event_id', 'events.id')->select('certificates.uid', 'users.fullname', 'users.username', 'events.name', 'certificates.type', 'certificates.position', 'certificates.category')->where('users.username', '=', Auth::user()->username)->where('certificates.uid', 'LIKE', "%{$search}%")->orWhere('users.fullname', 'LIKE', "%{$search}%")->orWhere('events.name', 'LIKE', "%{$search}%")->orWhere('certificates.type', 'LIKE', "%{$search}%")->orWhere('certificates.position', 'LIKE', "%{$search}%")->orWhere('certificates.category', 'LIKE', "%{$search}%")->orderBy($sortBy, $sortOrder)->paginate($pagination)->withQueryString();
                }
            }else{
                if(Gate::allows('authAdmin')){
                    $certificates = Certificate::join('users', 'certificates.user_id', 'users.id')->join('events', 'certificates.event_id', 'events.id')->select('certificates.uid', 'users.fullname', 'users.username', 'events.name', 'certificates.type', 'certificates.position', 'certificates.category')->orderBy($sortBy, $sortOrder)->paginate($pagination)->withQueryString();
                }else{
                    $certificates = Certificate::join('users', 'certificates.user_id', 'users.id')->join('events', 'certificates.event_id', 'events.id')->select('certificates.uid', 'users.fullname', 'users.username', 'events.name', 'certificates.type', 'certificates.position', 'certificates.category')->where('users.username', '=', Auth::user()->username)->orderBy($sortBy, $sortOrder)->paginate($pagination)->withQueryString();
                }
            }
            $sortAndSearch = [
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
                'search' => $search
            ];
            return view('certificate.list')->with(['sortAndSearch' => $sortAndSearch, 'certificates' => $certificates, 'appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'systemSetting' => $this->systemSetting]);
        }else{
            if(Gate::allows('authAdmin')){
                $certificates = Certificate::join('users', 'certificates.user_id', 'users.id')->join('events', 'certificates.event_id', 'events.id')->select('certificates.uid', 'users.fullname', 'users.username', 'events.name', 'certificates.type', 'certificates.position', 'certificates.category')->paginate($pagination)->withQueryString();
            }else{
                $certificates = Certificate::join('users', 'certificates.user_id', 'users.id')->join('events', 'certificates.event_id', 'events.id')->select('certificates.uid', 'users.fullname', 'users.username', 'events.name', 'certificates.type', 'certificates.position', 'certificates.category')->where('users.username', '=', Auth::user()->username)->paginate($pagination)->withQueryString();
            }
            return view('certificate.list')->with(['certificates' => $certificates, 'appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'systemSetting' => $this->systemSetting]);
        }
    }

    public function updateCertificateView(Request $request, $uid){
        if(Certificate::where('uid', $uid)->first()){
            // Only admins can update certificates
            if(Gate::allows('authAdmin')){
                $data = Certificate::where('uid', $uid)->join('events', 'certificates.event_id', '=', 'events.id')->first();
                return view('certificate.update')->with(['appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'systemSetting' => $this->systemSetting, 'data' => $data]);
            }else{
                abort(403, 'Anda tidak boleh mengakses laman ini.');
            }
        }else{
            abort(404, 'Sijil tidak dijumpai.');
        }
    }

    public function addCertificate(Request $request){
        $validated = $request->validate([
            'username' => ['required'],
            'event-id' => ['required'],
            'certificate-type' => ['required'],
            'position' => ['required']
        ]);

        $uidArray = Certificate::select('uid')->get();
        $uid = $this->generateUID(15, $uidArray);
        $username = strtolower($request->input('username'));
        $eventID = $request->input('event-id');
        $certificateType = strtolower($request->input('certificate-type'));
        $position = strtolower($request->input('position'));
        if($request->category !== NULL && $request->category !== ''){
            $category = $request->category;
        }else{
            $category = '';
        }
        // Recheck if username and event id is existed in case user doesn't input one that actually existed although with the JS help lel
        if(User::where('username', $username)->first()){
            if(Event::where('id', $eventID)->first()){
                $userID = User::select('id')->where('username', $username)->first()->id;
                Certificate::create([
                    'uid' => $uid,
                    'user_id' => $userID,
                    'event_id' => $eventID,
                    'type' => $certificateType,
                    'position' => $position,
                    'category' => $category
                ]);
                $request->session()->flash('addCertificateSuccess', 'Sijil berjaya ditambah!');
                return back();
            }else{
                return back()->withInput()->withErrors([
                    'event-id' => 'ID Acara tidak dijumpai!'
                ]);
            }
        }else{
            return back()->withInput()->withErrors([
                'username' => 'Username Pengguna tidak dijumpai!'
            ]);
        }
    }

    public function addCertificateBulk(Request $request){
        $validated = $request->validate([
            'certificate_list' => ['required', 'mimes:xlsx']
        ]);
        $inputFile = $request->file('certificate_list');
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly('1'); // Only read from worksheet named 1
        try {
            $spreadsheet = $reader->load($inputFile);
        } catch(PHPSpreadsheetReaderException $e) {
            die('Error loading file: '.$e->getMessage());
        }
        // Get all available rows. If a row is empty (in the username field), the rest of the row will be ignored. Warn the admin.
        $rows = $spreadsheet->getActiveSheet()->rangeToArray('B8:B507', NULL, FALSE, FALSE, TRUE);

        // Check if event ID inserted and exist
        $eventID = $spreadsheet->getActiveSheet()->getCell('C6')->getValue();
        if(empty($eventID)){
            return back()->withErrors([
                'sheetEventEmpty' => '[C6] ID acara kosong!',
            ]);
        }

        if(!Event::where('id', $eventID)->first()){
            return back()->withErrors([
                'sheetEventNotFound' => '[C6] Acara tidak dijumpai!',
            ]);
        }

        if(empty($rows[8]['B'])){
            return back()->withErrors([
                'sheetAtleastOne' => 'Sekurang-kurangnya satu data sijil diperlukan!',
            ]);
        }

        $availableRows = [];
        foreach ($rows as $row => $value) {
            if(!empty($value['B'])){
                array_push($availableRows, $row);
            }else{
                // If one row is empty in the username field, all the following rows will be ignored.
                break;
            }
        }

        $certificateData = [];
        foreach ($availableRows as $row) {
            $data = $spreadsheet->getActiveSheet()->rangeToArray('B' . $row . ':E' . $row, NULL, FALSE, FALSE, TRUE);
            array_push($certificateData, $data);
        }
        $spreadsheetErr = [];
        $validCertificateList = [];
        foreach($certificateData as $dataIndex){
            foreach($dataIndex as $data){
                $username = strtolower($data['B']);
                $type = $data['C'];
                $position = strtolower($data['D']);
                $category = strtolower($data['E']);
                $currentRow = key($dataIndex);

                // Check if user already
                if($username == 'admin'){
                    // Check if adding user named 'admin'
                    $error = '[B' . $currentRow . '] ' . 'Anda tidak boleh menambah sijil untuk pengguna: admin!';
                    array_push($spreadsheetErr, $error);
                }elseif(!User::where('username', strtolower($username))->first()){
                    // If user not found
                    $error = '[B' . $currentRow . '] ' . 'Pengguna dengan username tersebut tidak dijumpai!';
                    array_push($spreadsheetErr, $error);
                }else{
                    $userIDValid = User::select('id')->where('username', $username)->first()->id;
                    // Certificate type
                    if(empty($type)){
                        $error = '[C' . $currentRow . '] ' . 'Ruangan jenis kosong!';
                        array_push($spreadsheetErr, $error);
                    }else{
                        // Check if type valid
                        // Only 1, 2 and 3 can be inserted for type
                        // 1 = participation
                        // 2 = achievement
                        // 3 = appreciation
                        if($type == 1 || $type == 2 || $type == 3){
                            switch($type){
                                case 1:
                                    $typeValid = 'participation';
                                    break;
                                case 2:
                                    $typeValid = 'achievement';
                                    break;
                                case 3:
                                    $typeValid = 'appreciation';
                                    break;
                                default:
                            }
                        }else{
                            $error = '[C' . $currentRow . '] ' . 'Hanya masukkan nombor yang diperlukan di ruangan jenis!';
                            array_push($spreadsheetErr, $error);
                        }
                    }

                    if(empty($position)){
                        $error = '[D' . $currentRow . '] ' . 'Ruangan posisi kosong!';
                        array_push($spreadsheetErr, $error);
                    }else{
                        $positionValid = $position;
                    }
                    
                    if(empty($category)){
                        $categoryValid = NULL;
                    }else{
                        $categoryValid = $category;
                    }
    
                    // Check if all valid data have been set
                    if(!empty($userIDValid) && !empty($eventID) && !empty($typeValid) && !empty($positionValid) && !empty($categoryValid)){
                        $uidArray = Certificate::select('uid')->get();
                        $uid = $this->generateUID(15, $uidArray);
                        $validCertificate = [
                            'uid' => $uid,
                            'user_id' => $userIDValid,
                            'event_id' => $eventID,
                            'type' => $typeValid,
                            'position' => $positionValid,
                            'category' => $categoryValid
                        ];
                        array_push($validCertificateList, $validCertificate);
                    }
                }
            }
        }
        // If if there's no problem with the spreadsheet, if doesn't, proceed to add the users.
        if(count($spreadsheetErr) > 0){
            $request->session()->flash('spreadsheetErr', $spreadsheetErr);
            return back();
        }else{
            Certificate::upsert($validCertificateList, ['uid'], ['uid', 'user_id', 'event_id', 'type', 'position', 'category']);
            $request->session()->flash('spreadsheetSuccess', count($validCertificateList) . ' sijil berjaya ditambah secara pukal!');
            return back();
        }

    }

    // Generate unique UID for certificates
    protected function generateUID($length, $uidArray){
        $uid = Str::random($length);
        if(count($uidArray) > 0){
            for ($i=0; $i < count($uidArray); $i++) { 
                $dbUID = $uidArray[$i]->uid;
                while($uid == $dbUID){
                    $uid = Str::random($length);
                }
            }
        }else{
            $uid = Str::random($length);
        }
        return $uid;
    }

    public function removeCertificate(Request $request){
        $uid = $request->input('certificate-id');
        $certificate = Certificate::where('uid', $uid);
        $certificate->delete();
        $request->session()->flash('removeCertificateSuccess', 'Sijil berjaya dibuang!');
        return back();
    }

    public function updateCertificate(Request $request, $uid){
        $validated = $request->validate([
            'user-id' => ['required'],
            'event-id' => ['required'],
            'certificate-type' => ['required'],
            'position' => ['required']
        ]);

        if($request->category !== NULL && $request->category !== ''){
            $category = $request->category;
        }else{
            $category = '';
        }

        $eventID = $request->input('event-id');
        $certificateType = strtolower($request->input('certificate-type'));
        $position = strtolower($request->input('position'));

        // Recheck if username and event id is existed in case user doesn't input one that actually existed although with the JS help lel
        if(User::where('id', $request->input('user-id'))->first()){
            if(Event::where('id', $eventID)->first()){
                Certificate::updateOrCreate(
                    ['uid' => $uid],
                    [
                        'user_id' => $request->input('user-id'),
                        'event_id' => $eventID,
                        'type' => $certificateType,
                        'position' => $position,
                        'category' => $category
                    ]
                );
                $request->session()->flash('updateCertificateSuccess', 'Sijil berjaya dikemas kini!');
                return back();
            }else{
                return back()->withInput()->withErrors([
                    'event-id' => 'ID Acara tidak dijumpai!'
                ]);
            }
        }else{
            return back()->withInput()->withErrors([
                'username' => 'Username Pengguna tidak dijumpai!'
            ]);
        }
    }

    public function certificateView(Request $request, $uid){
        if(Certificate::where('uid', $uid)->first()){
            $certEvent = Certificate::where('uid', $uid)->first()->event;
            $certID = Certificate::select('id')->where('uid', $uid)->first()->id;
            // Add view activity history
            CertificateViewActivity::create([
                'certificate_id' => $certID,
                'ip_address' => $request->ip(),
                'http_user_agent' => $request->server('HTTP_USER_AGENT')
            ]);
            // Check for event visibility
            switch ($certEvent->visibility) {
                case 'public':
                    // Certificate PDF generated will have QR code with the URL to the certificate in it
                    return $this->generateCertificateHTML($uid, 'stream');
                    break;
                case 'hidden':
                    // Check if logged in
                    if(Auth::check()){
                        if(Gate::allows('authAdmin') || Certificate::where('uid', $uid)->first()->user_id == Auth::user()->id){
                            return $this->generateCertificateHTML($uid, 'stream');
                        }else{
                            return redirect()->route('home');
                        }
                    }else{
                        return redirect()->route('home');
                    }
                    break;
                default:
                    break;
            }
        }else{
            abort(404, 'Sijil tidak dijumpai.');
        }
    }

    protected function generateCertificate($certificateID, $mode = 'I', $savePath = NULL){
        $certUser = Certificate::where('uid', $certificateID)->first()->user;
        $certEvent = Certificate::where('uid', $certificateID)->first()->event;

        $borderAvailability = $certEvent->border;
        if($certEvent->text_color !== NULL && $certEvent->text_color !== ''){
            $textColor = TCPDF_COLORS::convertHTMLColorToDec($certEvent->text_color, TCPDF_COLORS::$spotcolor);
            $textColorR = $textColor["R"];
            $textColorG = $textColor["G"];
            $textColorB = $textColor["B"];
        }else{
            $textColorR = 0;
            $textColorG = 0;
            $textColorB = 0;
        }
        PDF::SetTextColor($textColorR, $textColorG, $textColorB);
        if($certEvent->border_color !== NULL && $certEvent->border_color !== ''){
            $borderColor = TCPDF_COLORS::convertHTMLColorToDec($certEvent->border_color, TCPDF_COLORS::$spotcolor);
        }else{
            $borderColor = TCPDF_COLORS::convertHTMLColorToDec("#000000", TCPDF_COLORS::$spotcolor);
        }
        
        // Check if border is gonna be used from the database
        switch ($borderAvailability) {
            case 'available':
                $border = TRUE;
                break;
            case 'unavailable':
                $border = FALSE;
                break;
            default:
                break;
        }

        $userFullname = strtoupper($certUser->fullname);
        $userIdentificationNumber = $certUser->identification_number;
        $eventName = strtoupper($certEvent->name);
        $dateExploded = explode("-", $certEvent->date);
        $eventDate = $dateExploded[2] . '/' . $dateExploded[1] . '/' . $dateExploded[0];
        $eventLocation = strtoupper($certEvent->location);
        $eventOrganiserName = strtoupper($certEvent->organiser_name);
        $certificateType = Certificate::where('uid', $certificateID)->first()->type;
        $certificationPosition = strtoupper(Certificate::where('uid', $certificateID)->first()->position);
        $backgroundImagePath = $certEvent->background_image;

        // Generate the certificate

        // Try to find way to add custom fonts
        $title = ucwords($this->systemSetting->name) . ' - ' . 'SeaJell';
        PDF::SetCreator('SeaJell');
        PDF::SetAuthor('SeaJell');
        switch ($certificateType) {
            case 'participation':
                $certificateTypeText = 'Sijil Penyertaan';
                break;
            case 'achievement':
                $certificateTypeText = 'Sijil Pencapaian';
                break;
            case 'appreciation':
                $certificateTypeText = 'Sijil Penghargaan';
                break;
            default:
                break;
        }
        PDF::SetTitle($title . ' - ' . $certificateTypeText);
        PDF::AddPage();
        PDF::SetMargins(0, 0, 0, true);
        PDF::SetAutoPageBreak(false, 0);
        PDF::setPageMark();

        /**
         * Background Color/Image
         * 
         * Check if no background image added.
         */

        if($certEvent->background_image != '' AND $certEvent->background_image != NULL){
            if(Storage::disk('public')->exists($backgroundImagePath)){
                $backgroundImage = '.' . Storage::disk('local')->url($backgroundImagePath);
            }
            PDF::Image($backgroundImage, 0, 0, 210, 297, '', '', '', false, 600, '', false, false, 0);
        }else{
            $backgroundColor = array(255, 255, 255);
            PDF::Rect(0, 0, 210, 297,'F', array(), $backgroundColor);
        }
        

        // Border
        switch ($border) {
            case TRUE:
                $borderStyle = array('width' => 1, 'color' => $borderColor);
                PDF::Rect(5, 5, 200, 287, 'D', array('all' => $borderStyle));
                break;
            case FALSE:
                break;
            default:
                break;
        }

        // Custom fonts
        PDF::addFont('cookie');
        PDF::addFont('badscript');
        PDF::addFont('bebasneue');
        PDF::addFont('poppins');
        PDF::addFont('poiretone');
        PDF::addFont('lobster');
        PDF::addFont('oleoscript');
        PDF::addFont('architectsdaughter');
        PDF::addFont('righteous');
        PDF::addFont('berkshireswash');
        PDF::addFont('satisfy');
        PDF::addFont('fredokaone');
        PDF::addFont('kaushanscript');
        PDF::addFont('rancho');
        PDF::addFont('carterone');

        // Font Sets
        // The font size must be set to match the font set appropriate sizes.
        // Had to use the same font for verifier name and position since it's the only best combination.
        // Font 1: Certificate type
        // Font 2: Odd
        // Font 3: Even
        // Font 4: Verifier name
        // Font 5: Verifier position
        $fontSetOne = ['cookie', 'badscript', 'bebasneue', 'bebasneue', 'poppins'];
        $fontSetTwo = ['lobster', 'poiretone', 'poppins', 'bebasneue', 'poppins'];
        $fontSetThree = ['oleoscript', 'architectsdaughter', 'righteous', 'bebasneue', 'poppins'];
        $fontSetFour = ['berkshireswash', 'satisfy', 'fredokaone', 'bebasneue', 'poppins'];
        $fontSetFive = ['kaushanscript', 'rancho', 'carterone', 'bebasneue', 'poppins'];

        // Adding 1.5 to the font sizes since it's too small. After testing and evaluating, the font sizes below should be good enough. I think.
        // Hoping there's no one or event that their name is too long.
        $fontSizeOne = [45, 14, 14, 14.5, 11.5];
        $fontSizeTwo = [36, 15.5, 12.5, 14.5, 11.5];
        $fontSizeThree = [36.5, 14.5, 13, 14.5, 11.5];
        $fontSizeFour = [33.5, 15.5, 12.5, 14.5, 11.5];
        $fontSizeFive = [31.5, 18, 12.5, 14.5, 11.5];

        // To Do: Font set selection on add event page
        switch ($certEvent->font_set) {
            case 1:
                $font = $fontSetOne;
                $fontSize = $fontSizeOne;
                break;
            case 2:
                $font = $fontSetTwo;
                $fontSize = $fontSizeTwo;
                break;
            case 3:
                $font = $fontSetThree;
                $fontSize = $fontSizeThree;
                break;
            case 4:
                $font = $fontSetFour;
                $fontSize = $fontSizeFour;
                break;
            case 5:
                $font = $fontSetFive;
                $fontSize = $fontSizeFive;
                break;
            default:
                $font = $fontSetOne;
                $fontSize = $fontSizeOne;
                break;
        }
        /**
         * Logos
         * Check if institute logo is available
         */

        if($certEvent->logo_first !== '' && $certEvent->logo_first !== NULL && $certEvent->logo_second !== '' && $certEvent->logo_second !== NULL && $certEvent->logo_third !== '' && $certEvent->logo_third !== NULL){
            // If 3 Logo
            if(Storage::disk('public')->exists($certEvent->logo_first)){
                $logoFirstImage = '.' . Storage::disk('local')->url($certEvent->logo_first);
                PDF::Image($logoFirstImage, $x = 30, $y = 7, $w = 40, $h = 40);
            }
            if(Storage::disk('public')->exists($certEvent->logo_second)){
                $logoSecondImage = '.' . Storage::disk('local')->url($certEvent->logo_second);
                PDF::Image($logoSecondImage, $x = 85, $y = 7, $w = 40, $h = 40);
            }
            if(Storage::disk('public')->exists($certEvent->logo_third)){
                $logoThirdImage = '.' . Storage::disk('local')->url($certEvent->logo_third);
                PDF::Image($logoThirdImage, $x = 140, $y = 7, $w = 40, $h = 40);
            }
        }elseif($certEvent->logo_first !== '' && $certEvent->logo_first !== NULL && $certEvent->logo_second !== '' && $certEvent->logo_second !== NULL){
            // If 2 Logo
            if(Storage::disk('public')->exists($certEvent->logo_first)){
                $logoFirstImage = '.' . Storage::disk('local')->url($certEvent->logo_first);
                PDF::Image($logoFirstImage, $x = 55, $y = 7, $w = 40, $h = 40);
            }
            if(Storage::disk('public')->exists($certEvent->logo_second)){
                $logoSecondImage = '.' . Storage::disk('local')->url($certEvent->logo_second);
                PDF::Image($logoSecondImage, $x = 115, $y = 7, $w = 40, $h = 40);
            }
        }elseif($certEvent->logo_first !== '' && $certEvent->logo_first !== NULL){
            // If 1 Logo
            if(Storage::disk('public')->exists($certEvent->logo_first)){
                $logoImage = '.' . Storage::disk('local')->url($certEvent->logo_first);
                PDF::Image($logoImage, $x = 85, $y = 7, $w = 40, $h = 40);
            }
        }

        PDF::Ln(35);
        PDF::SetFont($font[0], 'BI', $fontSize[0]);
        PDF::Cell($w = 0, $h = 1, $txt = $certificateTypeText, $align = 'C', $border = '1', $calign = 'C');

        switch ($certificateType) {
            case 'participation':
                $certificateIntro = 'Adalah dengan ini diakui bahawa';
                break;
            case 'achievement':
                $certificateIntro = 'Setinggi-tinggi tahniah diucapkan kepada';
                break;
            case 'appreciation':
                $certificateIntro = 'Setinggi-tinggi penghargaan dan terima kasih kepada';
                break;
            default:
                break;
        }

        PDF::SetFont($font[1], 'I', $fontSize[1]);
        PDF::Cell($w = 0, $h = 1, $txt = $certificateIntro, $align = 'C', $border = '1', $calign = 'C');

        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::MultiCell(190, 0, $userFullname, 0, 'C', 0, 1, 10);

        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::Cell($w = 0, $h = 1, $txt = '(' . $userIdentificationNumber . ')', $align = 'C', $border = '1', $calign = 'C');
        switch ($certificateType) {
            case 'participation':
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'Telah menyertai', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::MultiCell(190, 0, $eventName, 0, 'C', 0, 1, 10);
                break;
            case 'achievement':
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'Di atas pencapaian', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::MultiCell(190, 0, $certificationPosition, 0, 'C', 0, 1, 10);
                
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'Dalam', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::MultiCell(190, 0, $eventName, 0, 'C', 0, 1, 10);
                break;
            case 'appreciation':
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'atas sumbangan dan komitmen sebagai', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::Cell($w = 0, $h = 1, $txt = $certificationPosition, $align = 'C', $border = '1', $calign = 'C');
                
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'Dalam', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::MultiCell(190, 0, $eventName, 0, 'C', 0, 1, 10);
                break;
            default:
                break;
        }
        
        if(Certificate::where('uid', $certificateID)->first()){
            if(Certificate::where('uid', $certificateID)->first()->category){
                $category = Certificate::where('uid', $certificateID)->first()->category;
                if($category !== NULL && $category !== ''){
                    PDF::SetFont($font[1], 'I', $fontSize[1]);
                    PDF::Cell($w = 0, $h = 1, $txt = 'Kategori', $align = 'C', $border = '1', $calign = 'C');
                    PDF::SetFont($font[2], 'B', $fontSize[2]);
                    PDF::MultiCell(190, 0, strtoupper($category), 0, 'C', 0, 1, 10);
                }
            }
        }
        
        PDF::SetFont($font[1], 'I', $fontSize[1]);
        PDF::Cell($w = 0, $h = 1, $txt = 'Pada', $align = 'C', $border = '1', $calign = 'C');

        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::MultiCell(190, 0, $eventDate, 0, 'C', 0, 1, 10);

        PDF::SetFont($font[1], 'I', $fontSize[1]);
        PDF::Cell($w = 0, $h = 1, $txt = 'Bertempat di', $align = 'C', $border = '1', $calign = 'C');
        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::MultiCell(190, 0, $eventLocation, 0, 'C', 0, 1, 10);

        PDF::SetFont($font[1], 'I', $fontSize[1]);
        PDF::Cell($w = 0, $h = 1, $txt = 'Anjuran', $align = 'C', $border = '1', $calign = 'C');
        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::MultiCell(190, 0, $eventOrganiserName, 0, 'C', 0, 1, 10);

        /**
         * Signatures
         */

        $dots = '............................................';

        // If 3 signature
        if($certEvent->signature_first !== '' && $certEvent->signature_first !== NULL && $certEvent->signature_second !== '' && $certEvent->signature_second !== NULL && $certEvent->signature_third !== '' && $certEvent->signature_third !== NULL){
            // First
            if(Storage::disk('public')->exists($certEvent->signature_first)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_first);
            }
            PDF::Image($certificationVerifierSignature, $x = 25, $y = 200, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 21, 210.5);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_first_name) . ')', 0, 'C', 0, 0, 21);
            PDF::Ln();
            PDF::SetFont($font[4], '', $fontSize[4]);
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_first_position), 0, 'C', 0, 0, 21);

            // Second
            if(Storage::disk('public')->exists($certEvent->signature_second)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_second);
            }
            PDF::Image($certificationVerifierSignature, $x = 82, $y = 200, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 78, 210.5);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_second_name) . ')', 0, 'C', 0, 0, 78);
            PDF::Ln();
            PDF::SetFont($font[4], '', $fontSize[4]);
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_second_position), 0, 'C', 0, 0, 78);

            // Third
            if(Storage::disk('public')->exists($certEvent->signature_third)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_third);
            }
            PDF::Image($certificationVerifierSignature, $x = 140, $y = 200, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 136, 210.5);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_third_name) . ')', 0, 'C', 0, 0, 136);
            PDF::Ln();
            PDF::SetFont($font[4], '', $fontSize[4]);
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_third_position), 0, 'C', 0, 0, 136);
        }elseif($certEvent->signature_first !== '' && $certEvent->signature_first !== NULL && $certEvent->signature_second !== '' && $certEvent->signature_second !== NULL){
            // If 2 signature

            // First
            if(Storage::disk('public')->exists($certEvent->signature_first)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_first);
            }
            PDF::Image($certificationVerifierSignature, $x = 50, $y = 200, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 46, 210.5);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_first_name) . ')', 0, 'C', 0, 0, 46);
            PDF::Ln();
            PDF::SetFont($font[4], '', $fontSize[4]);
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_first_position), 0, 'C', 0, 0, 46);

            // Second
            if(Storage::disk('public')->exists($certEvent->signature_second)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_second);
            }
            PDF::Image($certificationVerifierSignature, $x = 110, $y = 200, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 106, 210.5);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_second_name) . ')', 0, 'C', 0, 0, 106);
            PDF::Ln();
            PDF::SetFont($font[4], '', $fontSize[4]);
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_second_position), 0, 'C', 0, 0, 106);
        }elseif($certEvent->signature_first !== '' && $certEvent->signature_first !== NULL){
            // If 1 signature

            // First
            if(Storage::disk('public')->exists($certEvent->signature_first)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_first);
            }
            PDF::Image($certificationVerifierSignature, $x = 82, $y = 200, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 78, 210.5);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_first_name) . ')', 0, 'C', 0, 0, 78);
            PDF::Ln();
            PDF::SetFont($font[4], '', $fontSize[4]);
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_first_position), 0, 'C', 0, 0, 78);
        }
       
        /**
         * QR code
         */
        switch ($certEvent->visibility) {
            case 'public':
                $style = array(
                    'border' => false,
                    'vpadding' => 0.5,
                    'hpadding' => 0.5,
                    'fgcolor' => array(0,0,0),
                    'bgcolor' => array(255, 255, 255),
                    'module_width' => 1, 
                    'module_height' => 1
                );
                
                if($borderColor !== NULL && $borderColor != ''){
                    $QRBorderColor = $borderColor;
                }else{
                    $QRBorderColor = array(0, 0, 0);
                }
                PDF::SetTextColor(0, 0, 0);
                PDF::Rect(135, 272.5, 70, 19.5, 'DF', array('all' => array('width' => 0.5, 'color' => $QRBorderColor)), array(255, 255, 255));
                PDF::SetFont('bebasneue', '', 12);
                PDF::MultiCell($w = 50, $h = 0, $txt = 'Imbas Kod QR Ini Untuk Menyemak Ketulenan', 0, 'L', 0, 0, 137, 274);
                PDF::MultiCell($w = 50, $h = 0, $txt = 'ID Sijil: ' . $certificateID, 0, 'L', 0, 0, 137, 286);
                PDF::write2DBarcode(url('certificate/authenticity/' . $certificateID), 'QRCODE,Q', 186, 273, 18.5, 18.5, $style, 'N');
                break;
            case 'hidden':
                break;
            default:
                break;
        }
        
        switch($mode){
            case 'I':
                PDF::Output('SeaJell_e_Certificate_' . $certificateID . '.pdf', 'I');
                break;
            case 'F':
                if(!empty($savePath)){
                    $fullPath = $savePath . 'SeaJell_e_Certificate_' . $certificateID . '.pdf';
                    PDF::Output($fullPath, 'F');
                }
                break;
            default:
            PDF::Output('SeaJell_e_Certificate_' . $certificateID . '.pdf', 'I');
                break;
        }
        PDF::reset();
    }

    protected function generateCertificateHTML($certificateID, $mode = 'stream', $savePath = NULL){
        $certificateFileName = 'SeaJell_e_Certificate_' . $certificateID . '.pdf';
        $eventID = Certificate::select('event_id')->where('uid', $certificateID)->first()->event_id;
        $eventData = Event::where('id', $eventID)->first();
        $eventFontData = EventFont::where('event_id', $eventID)->first();
        $certificateData = Certificate::select('certificates.uid', 'users.fullname', 'users.identification_number', 'certificates.position', 'certificates.category', 'certificates.type')->where('uid', $certificateID)->join('users', 'certificates.user_id', '=', 'users.id')->first();
        $viewData = ['appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'systemSetting' => $this->systemSetting, 'eventData' => $eventData, 'eventFontData' => $eventFontData, 'certificateData' => $certificateData];
        // Check whether wanna save or stream the certificate
        
        switch($mode){
            case 'stream':
                $pdf = PDF::loadView('certificate.layout', $viewData)->setPaper('a4', 'potrait')->setWarnings(false)->stream($certificateFileName);
                return $pdf;
            case 'save':
                $fullPath = $savePath . 'SeaJell_e_Certificate_' . $certificateID . '.pdf';
                PDF::loadView('certificate.layout', $viewData)->setPaper('a4', 'potrait')->setWarnings(false)->save($fullPath);
            default:
                $pdf = PDF::loadView('certificate.layout', $viewData)->setPaper('a4', 'potrait')->setWarnings(false)->stream($certificateFileName);
                return $pdf;
        }
    }

    protected function saveCertificateCollection($request, $certificates, $folderFor, $historyDataUser, $historyDataEvent){
        $currentTimestamp = Carbon::now()->timestamp;
        $folderName = $folderFor . $currentTimestamp; // Folder name to save all certificate in storage/app/certificate folder
        $savePath = storage_path('app/certificate/' . $folderName . '/');
        Storage::disk('local')->makeDirectory('/certificate/' . $folderName);
        // Saves all certificates relating to user to storage/app/certificate folder
        foreach ($certificates as $certificate) {
            $this->generateCertificateHTML($certificate->uid, 'save', $savePath);
        }
        // Archive all certificates to a zip file
        $zipName = $folderName . '.zip';
        $zip = new ZipArchive;
        $zipSavePath = storage_path('app/certificate/' . $folderName . '/' . $zipName);
        if ($zip->open($zipSavePath, ZipArchive::CREATE) === TRUE){
            $files = Storage::disk('local')->files('/certificate/' . $folderName);
            foreach ($files as $key => $value) {
                $fileNameInZip = basename($value);
                $filePath = storage_path('app/') . $value;
                $zip->addFile($filePath, $fileNameInZip);
            }
            
            $zip->close();
        }
        $downloadZipPath = asset('certificate/' . $folderName . '/' . $folderName . '.zip');
        // Certificates Total
        $certificatesTotal = $certificates->count();
        // Current Date and Next 24 hour
        $currentDateTime = Carbon::now()->toDateTimeString();
        $next24HourDateTime = Carbon::parse($currentDateTime)->addDays(1);
        CertificateCollectionHistory::create([
            'requested_by' => Auth::user()->id,
            'requested_on' => $currentDateTime,
            'next_available_download' => $next24HourDateTime,
            'user_id' => $historyDataUser,
            'event_id' => $historyDataEvent,
            'certificates_total' => $certificatesTotal
        ]);
        CertificateCollectionDeletionSchedule::create([
            'folder_name' => $folderName,
            'delete_after' => $next24HourDateTime
        ]);
        return $downloadZipPath;
    }

    public function downloadCertificateCollection(Request $request){
        /**
         * Admin can download all certificates for a participant or an event.
         * Participant can download certificates only for themselves.
         * Downloads for event or user is limited to the user who tries to download it to every 24 hours.
         */
        if(Gate::allows('authAdmin')){
            // Admin tries to download
            $validated = $request->validate([
                'id_username' => ['required']
            ]);
            if($request->has('collection_download_options')){
                switch ($request->input('collection_download_options')) {
                    case 'participant':
                        if(User::where('role', 'participant')->where('username' , strtolower($request->input('id_username')))->first()){
                            // Checks if certificate exist.
                            if(Certificate::join('users', 'certificates.user_id', 'users.id')->where('users.username', strtolower($request->input('id_username')))->first()){
                                // Check for download limit
                                $participantID = User::select('id')->where('username', strtolower($request->input('id_username')))->first()->id;
                                $certificates = Certificate::join('users', 'certificates.user_id', 'users.id')->where('users.username', strtolower($request->input('id_username')))->select('certificates.uid')->get();
                                $folderFor = 'user_';
                                $historyDataUser = User::select('id')->where('username', strtolower($request->input('id_username')))->first()->id;
                                $historyDataEvent = NULL;
                                // If collection download is for a user and is done by current authenticated user
                                if(CertificateCollectionHistory::where('user_id', $participantID)->where('requested_by', Auth::user()->id)->first()){
                                    $latestRequest = CertificateCollectionHistory::where('user_id', $participantID)->where('requested_by', Auth::user()->id)->latest('requested_on')->first();
                                    if(Carbon::now()->toDateTimeString() > $latestRequest->next_available_download){
                                        // If the date already passed the last download for 24 hour
                                        $request->session()->flash('collectionDownloadSuccessPath', $this->saveCertificateCollection($request, $certificates, $folderFor, $historyDataUser, $historyDataEvent));
                                        return back();
                                    }else{
                                        return back()->withErrors([
                                            'collectionLimit' => $latestRequest->next_available_download,
                                        ]);
                                    }
                                }else{
                                    // No old download history
                                    $request->session()->flash('collectionDownloadSuccessPath', $this->saveCertificateCollection($request, $certificates, $folderFor, $historyDataUser, $historyDataEvent));
                                    return back();
                                }
                            }else{
                                return back()->withErrors([
                                    'collectionNoCertificate' => 'Tiada sijil untuk koleksi tersebut!',
                                ]);
                            }
                        }else{
                            return back()->withErrors([
                                'collectionUserNotFound' => 'Peserta tidak wujud!',
                            ]);
                        }
                        break;
                    case 'event':
                        if(Event::where('id', $request->input('id_username'))->first()){
                            // Checks if certificate exist
                            if(Certificate::where('event_id', $request->input('id_username'))->first()){
                                // Check for download limit
                                $certificates = Certificate::where('event_id', $request->input('id_username'))->select('certificates.uid')->get();
                                $folderFor = 'event_';
                                $historyDataUser = NULL;
                                $historyDataEvent = $request->input('id_username');
                                if(CertificateCollectionHistory::where('event_id', $request->input('id_username'))->first()){
                                    $latestRequest = CertificateCollectionHistory::where('event_id', $request->input('id_username'))->latest('requested_on')->first();
                                    if(Carbon::now()->toDateTimeString() > $latestRequest->next_available_download){
                                        // If the date already passed the last download for 24 hour
                                        $request->session()->flash('collectionDownloadSuccessPath', $this->saveCertificateCollection($request, $certificates, $folderFor, $historyDataUser, $historyDataEvent));
                                        return back();
                                    }else{
                                        return back()->withErrors([
                                            'collectionLimit' => $latestRequest->next_available_download,
                                        ]);
                                    }
                                }else{
                                    // No old download history
                                    $request->session()->flash('collectionDownloadSuccessPath', $this->saveCertificateCollection($request, $certificates, $folderFor, $historyDataUser, $historyDataEvent));
                                    return back();
                                }
                            }
                        }else{
                            return back()->withErrors([
                                'collectionEventNotFound' => 'Acara tidak wujud!',
                            ]);
                        }
                        break;
                    default:
                        return redirect()->back();
                }
            }
        }elseif(!Gate::allows('authAdmin')){
            // Participant downloads collection for themself
            if(Certificate::where('user_id', Auth::user()->id)->first()){
                // Check for download limit
                $participantID = Auth::user()->id;
                $certificates = Certificate::where('user_id', $participantID)->select('certificates.uid')->get();
                $folderFor = 'user_';
                $historyDataUser = $participantID;
                $historyDataEvent = NULL;
                // If collection download is for a user and is done by current authenticated user
                if(CertificateCollectionHistory::where('user_id', $participantID)->where('requested_by', Auth::user()->id)->first()){
                    $latestRequest = CertificateCollectionHistory::where('user_id', $participantID)->where('requested_by', Auth::user()->id)->latest('requested_on')->first();
                    if(Carbon::now()->toDateTimeString() > $latestRequest->next_available_download){
                        // If the date already passed the last download for 24 hour
                        $request->session()->flash('collectionDownloadSuccessPath', $this->saveCertificateCollection($request, $certificates, $folderFor, $historyDataUser, $historyDataEvent));
                        return back();
                    }else{
                        return back()->withErrors([
                            'collectionLimit' => $latestRequest->next_available_download,
                        ]);
                    }
                }else{
                    // No old download history
                    $request->session()->flash('collectionDownloadSuccessPath', $this->saveCertificateCollection($request, $certificates, $folderFor, $historyDataUser, $historyDataEvent));
                    return back();
                }
            }else{
                return back()->withErrors([
                    'collectionNoCertificate' => 'Tiada sijil untuk koleksi tersebut!',
                ]);
            }
        }else{
            return redirect()->back();
        }
    }
}