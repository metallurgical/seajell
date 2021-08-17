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
use TCPDF_FONTS;
use TCPDF_COLORS;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use App\Models\Certificate;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\MainController;

class CertificateController extends MainController
{
    public function addCertificateView(Request $request){
        return view('certificate.add')->with(['appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'orgName' => $this->orgName]);
    }
    public function certificateListView(Request $request){
        if(Gate::allows('authAdmin')){
            $certificates = Certificate::select('certificates.uid', 'certificates.type', 'certificates.position', 'certificates.category', 'users.fullname', 'events.name')->join('users', 'certificates.user_id', '=', 'users.id')->join('events', 'certificates.event_id', '=', 'events.id')->paginate(7);
            return view('certificate.list')->with(['appVersion' => $this->appVersion, 'certificates' => $certificates, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'orgName' => $this->orgName]);
        }else{
            $certificates = Certificate::select('certificates.uid', 'certificates.type', 'certificates.position', 'certificates.category', 'users.fullname', 'events.name')->join('users', 'certificates.user_id', '=', 'users.id')->join('events', 'certificates.event_id', '=', 'events.id')->where('username', Auth::user()->username)->paginate(7);
            return view('certificate.list')->with(['appVersion' => $this->appVersion, 'certificates' => $certificates, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'orgName' => $this->orgName]);
        }
    }

    public function updateCertificateView(Request $request, $uid){
        if(Certificate::where('uid', $uid)->first()){
            // Only admins can update certificates
            if(Gate::allows('authAdmin')){
                $data = Certificate::where('uid', $uid)->join('events', 'certificates.event_id', '=', 'events.id')->first();
                return view('certificate.update')->with(['appVersion' => $this->appVersion, 'apiToken' => $this->apiToken, 'appName' => $this->appName, 'orgName' => $this->orgName, 'data' => $data]);
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
        if(Certificate::where('uid', $uid)){
            $certEvent = Certificate::where('uid', $uid)->first()->event;
            // Check for event visibility
            switch ($certEvent->visibility) {
                case 'public':
                    // Certificate PDF generated will have QR code with the URL to the certificate in it
                    $this->generateCertificate($request, $uid, array(255, 254, 212), 'I');
                    break;
                case 'hidden':
                    // Check if logged in
                    if(Auth::check()){
                        if(Gate::allows('authAdmin') || Certificate::where('uid', $uid)->first()->user_id == Auth::user()->id){
                            $this->generateCertificate($request, $uid, array(255, 254, 212), 'I');
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
            abort(404, 'Sijil tidak dijumpai!');
        }
    }

    protected function generateCertificate($request, $certificateID, $backgroundColor = array(255, 254, 212), $mode = 'I'){
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
        $title = $this->orgName . ' - ' . 'SeaJell';
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
        $fontSetOne = ['cookie', 'badscript', 'bebasneue', 'bebasneue'];
        $fontSetTwo = ['lobster', 'poiretone', 'poppins', 'poppins'];
        $fontSetThree = ['oleoscript', 'architectsdaughter', 'righteous', 'righteous'];
        $fontSetFour = ['berkshireswash', 'satisfy', 'fredokaone', 'fredokaone'];
        $fontSetFive = ['kaushanscript', 'rancho', 'carterone', 'carterone'];

        $fontSizeOne = [45, 14, 14, 11];
        $fontSizeTwo = [40, 17, 12, 8.5];
        $fontSizeThree = [45, 15, 13, 8.8];
        $fontSizeFour = [45, 14.8, 11, 8];
        $fontSizeFive = [45, 16, 12, 8.8];

        $font = $fontSetOne;
        $fontSize = $fontSizeOne;
        /**
         * Logos
         * Check if institute logo is available
         */

        if($certEvent->logo_first !== '' && $certEvent->logo_first !== NULL && $certEvent->logo_second !== '' && $certEvent->logo_second !== NULL && $certEvent->logo_third !== '' && $certEvent->logo_third !== NULL){
            // If 3 Logo
            if(Storage::disk('public')->exists($certEvent->logo_first)){
                $logoFirstImage = '.' . Storage::disk('local')->url($certEvent->logo_first);
                PDF::Image($logoFirstImage, $x = 30, $y = 12, $w = 40, $h = 40);
            }
            if(Storage::disk('public')->exists($certEvent->logo_second)){
                $logoSecondImage = '.' . Storage::disk('local')->url($certEvent->logo_second);
                PDF::Image($logoSecondImage, $x = 85, $y = 12, $w = 40, $h = 40);
            }
            if(Storage::disk('public')->exists($certEvent->logo_third)){
                $logoThirdImage = '.' . Storage::disk('local')->url($certEvent->logo_third);
                PDF::Image($logoThirdImage, $x = 140, $y = 12, $w = 40, $h = 40);
            }
        }elseif($certEvent->logo_first !== '' && $certEvent->logo_first !== NULL && $certEvent->logo_second !== '' && $certEvent->logo_second !== NULL){
            // If 2 Logo
            if(Storage::disk('public')->exists($certEvent->logo_first)){
                $logoFirstImage = '.' . Storage::disk('local')->url($certEvent->logo_first);
                PDF::Image($logoFirstImage, $x = 55, $y = 12, $w = 40, $h = 40);
            }
            if(Storage::disk('public')->exists($certEvent->logo_second)){
                $logoSecondImage = '.' . Storage::disk('local')->url($certEvent->logo_second);
                PDF::Image($logoSecondImage, $x = 115, $y = 12, $w = 40, $h = 40);
            }
        }elseif($certEvent->logo_first !== '' && $certEvent->logo_first !== NULL){
            // If 1 Logo
            if(Storage::disk('public')->exists($certEvent->logo_first)){
                $logoImage = '.' . Storage::disk('local')->url($certEvent->logo_first);
                PDF::Image($logoImage, $x = 85, $y = 12, $w = 40, $h = 40);
            }
        }

        PDF::Ln(42);
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
        
        PDF::Ln(1);
        PDF::SetFont($font[1], 'I', $fontSize[1]);
        PDF::Cell($w = 0, $h = 1, $txt = $certificateIntro, $align = 'C', $border = '1', $calign = 'C');

        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::MultiCell(190, 0, $userFullname, 0, 'C', 0, 1, 10);

        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::Cell($w = 0, $h = 1, $txt = '(' . $userIdentificationNumber . ')', $align = 'C', $border = '1', $calign = 'C');
        switch ($certificateType) {
            case 'participation':
                PDF::Ln(2);
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'Telah menyertai', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::MultiCell(190, 0, $eventName, 0, 'C', 0, 1, 10);
                break;
            case 'achievement':
                PDF::Ln(2);
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'Di atas pencapaian', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::MultiCell(190, 0, $certificationPosition, 0, 'C', 0, 1, 10);
                
                PDF::Ln(2);
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'Dalam', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::MultiCell(190, 0, $eventName, 0, 'C', 0, 1, 10);
                break;
            case 'appreciation':
                PDF::Ln(2);
                PDF::SetFont($font[1], 'I', $fontSize[1]);
                PDF::Cell($w = 0, $h = 1, $txt = 'atas sumbangan dan komitmen sebagai', $align = 'C', $border = '1', $calign = 'C');

                PDF::SetFont($font[2], 'B', $fontSize[2]);
                PDF::Cell($w = 0, $h = 1, $txt = $certificationPosition, $align = 'C', $border = '1', $calign = 'C');
                
                PDF::Ln(2);
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
                    PDF::Ln(2);
                    PDF::SetFont($font[1], 'I', $fontSize[1]);
                    PDF::Cell($w = 0, $h = 1, $txt = 'Kategori', $align = 'C', $border = '1', $calign = 'C');
                    PDF::SetFont($font[2], 'B', $fontSize[2]);
                    PDF::MultiCell(190, 0, strtoupper($category), 0, 'C', 0, 1, 10);
                }
            }
        }
        
        PDF::Ln(2);
        PDF::SetFont($font[1], 'I', $fontSize[1]);
        PDF::Cell($w = 0, $h = 1, $txt = 'Pada', $align = 'C', $border = '1', $calign = 'C');

        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::MultiCell(190, 0, $eventDate, 0, 'C', 0, 1, 10);

        PDF::Ln(2);
        PDF::SetFont($font[1], 'I', $fontSize[1]);
        PDF::Cell($w = 0, $h = 1, $txt = 'Bertempat di', $align = 'C', $border = '1', $calign = 'C');
        PDF::SetFont($font[2], 'B', $fontSize[2]);
        PDF::MultiCell(190, 0, $eventLocation, 0, 'C', 0, 1, 10);

        PDF::Ln(2);
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
            PDF::Image($certificationVerifierSignature, $x = 25, $y = 216, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 21, 230);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_first_name) . ')', 0, 'C', 0, 0, 21);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_first_position), 0, 'C', 0, 0, 21);

            // Second
            if(Storage::disk('public')->exists($certEvent->signature_second)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_second);
            }
            PDF::Image($certificationVerifierSignature, $x = 82, $y = 216, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 78, 230);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_second_name) . ')', 0, 'C', 0, 0, 78);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_second_position), 0, 'C', 0, 0, 78);

            // Third
            if(Storage::disk('public')->exists($certEvent->signature_third)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_third);
            }
            PDF::Image($certificationVerifierSignature, $x = 140, $y = 216, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 136, 230);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_third_name) . ')', 0, 'C', 0, 0, 136);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_third_position), 0, 'C', 0, 0, 136);
        }elseif($certEvent->signature_first !== '' && $certEvent->signature_first !== NULL && $certEvent->signature_second !== '' && $certEvent->signature_second !== NULL){
            // If 2 signature

            // First
            if(Storage::disk('public')->exists($certEvent->signature_first)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_first);
            }
            PDF::Image($certificationVerifierSignature, $x = 50, $y = 216, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 46, 230);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_first_name) . ')', 0, 'C', 0, 0, 46);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_first_position), 0, 'C', 0, 0, 46);

            // Second
            if(Storage::disk('public')->exists($certEvent->signature_second)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_second);
            }
            PDF::Image($certificationVerifierSignature, $x = 110, $y = 216, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 106, 230);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_second_name) . ')', 0, 'C', 0, 0, 106);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = strtoupper($certEvent->signature_second_position), 0, 'C', 0, 0, 106);
        }elseif($certEvent->signature_first !== '' && $certEvent->signature_first !== NULL){
            // If 1 signature

            // First
            if(Storage::disk('public')->exists($certEvent->signature_first)){
                $certificationVerifierSignature = '.' . Storage::disk('local')->url($certEvent->signature_first);
            }
            PDF::Image($certificationVerifierSignature, $x = 82, $y = 216, $w = 46.8, $h = 15.6);

            PDF::SetFont($font[3], '', $fontSize[3]);
            PDF::MultiCell($w = 55, $h = 0, $txt = $dots, 0, 'C', 0, 0, 78, 230);
            PDF::Ln();
            PDF::MultiCell($w = 55, $h = 0, $txt = '(' . strtoupper($certEvent->signature_first_name) . ')', 0, 'C', 0, 0, 78);
            PDF::Ln();
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
                PDF::MultiCell($w = 50, $h = 0, $txt = 'Imbas kod QR ini untuk menyemak ketulenan', 0, 'R', 0, 0, 135, 277);
                PDF::write2DBarcode(url()->current(), 'QRCODE,Q', 186, 273, 18.5, 18.5, $style, 'N');
                break;
            case 'hidden':
                break;
            default:
                break;
        }

        PDF::Output('SeaJell_e_Certificate.pdf', $mode);
        PDF::reset();
    }
}