<!doctype html>
<html><head><meta charset="utf-8"><style>
@page{size:A4;margin:12mm}
body{font-family:DejaVu Sans, sans-serif;color:#252525;font-size:9px;line-height:1.35}
.top{display:table;width:100%;border-bottom:3px solid #c89835;padding-bottom:8px}
.cell{display:table-cell;vertical-align:top}
.photo{width:85px;height:100px;object-fit:cover;border:1px solid #999}
.placeholder{display:inline-block;width:85px;height:100px;border:1px solid #c89835;text-align:center;padding-top:38px;color:#a97718;font-weight:bold}
.brand{font-size:19px;font-weight:bold;color:#a97718}
.title{font-size:16px;font-weight:bold;color:#a97718;margin-top:5px}
.section{margin-top:9px;border:1px solid #e0d8c7;padding:7px 9px;background:#fff}
.section-title{font-size:10.5px;font-weight:bold;color:#a97718;border-bottom:1px solid #e5d4ad;padding-bottom:3px;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px}
.layout-table{width:100%;border-collapse:collapse;table-layout:fixed}
.layout-table td{padding:3px 6px 3px 0;vertical-align:top}
.label{font-size:7.5px;color:#777;text-transform:uppercase;display:block;margin-bottom:1px}
.value{font-weight:bold;font-size:9px;color:#222;word-break:break-word}
.table{width:100%;border-collapse:collapse;margin-top:3px}
.table th,.table td{border:1px solid #dcd3c3;padding:4px 6px;text-align:left;font-size:8.5px}
.table th{background:#fdfbf7;color:#a97718;font-weight:bold;text-transform:uppercase;font-size:8px}
.badge-status{display:inline-block;padding:2px 7px;border-radius:3px;font-weight:bold;font-size:8.5px}
.badge-verified{background:#dcfce7;color:#15803d;border:1px solid #86efac}
.badge-pending{background:#fef3c7;color:#b45309;border:1px solid #fcd34d}
.badge-rejected{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5}
.badge-partially_verified{background:#e0f2fe;color:#0369a1;border:1px solid #7dd3fc}
.signature{height:40px;border-bottom:1px solid #555;width:170px}
.stamp{max-width:75px;max-height:50px}
.footer{margin-top:10px;display:table;width:100%}
</style></head><body>
@php($w=$snapshot['worker']??[])
<div class="top">
  <div class="cell" style="width:75%">
    @if($logoData)<img src="{{ $logoData }}" style="max-width:80px;max-height:35px"><br>@endif
    <div class="brand">{{ $snapshot['agency']['business_name']??'Helper Home' }}</div>
    <div>{{ $snapshot['agency']['tagline']??'Home Care & Domestic Services' }}</div>
    <div class="title">WORKER REGISTRATION FORM</div>
  </div>
  <div class="cell" style="width:25%;text-align:right">
    @if($photoData)<img class="photo" src="{{ $photoData }}">@else<span class="placeholder">PHOTO</span>@endif
  </div>
</div>

<div class="section" style="background:#faf7f0">
  <table class="layout-table">
    <tr>
      <td style="width:33.3%"><span class="label">Registration No</span><span class="value" style="color:#a97718;font-size:9.5px">{{ $w['registration_number']??'—' }}</span></td>
      <td style="width:33.3%"><span class="label">Registration Date</span><span class="value">{{ $w['registration_date']??'—' }}</span></td>
      <td style="width:33.4%"><span class="label">Worker Code</span><span class="value" style="color:#a97718;font-size:9.5px">{{ $w['worker_code']??'—' }}</span></td>
    </tr>
  </table>
</div>

<div class="section">
  <div class="section-title">Personal Details</div>
  <table class="layout-table">
    <tr>
      <td style="width:25%"><span class="label">Worker Name</span><span class="value">{{ $w['name']??'—' }}</span></td>
      <td style="width:25%"><span class="label">Mobile</span><span class="value">{{ $w['mobile_number']??'—' }}</span></td>
      <td style="width:25%"><span class="label">Alternate Mobile</span><span class="value">{{ $w['alternate_mobile_number']??'—' }}</span></td>
      <td style="width:25%"><span class="label">Email</span><span class="value">{{ $w['email']??'—' }}</span></td>
    </tr>
    <tr>
      <td><span class="label">DOB / Age</span><span class="value">{{ $w['date_of_birth']??'—' }} @if(!empty($w['age'])) / {{ $w['age'] }} yrs @endif</span></td>
      <td><span class="label">Gender</span><span class="value">{{ !empty($w['gender'])?str($w['gender'])->headline():'—' }}</span></td>
      <td><span class="label">Marital Status</span><span class="value">{{ !empty($w['marital_status'])?str($w['marital_status'])->headline():'—' }}</span></td>
      <td><span class="label">Date of Joining</span><span class="value">{{ $w['date_of_joining']??'—' }}</span></td>
    </tr>
    <tr>
      <td colspan="2" style="padding-top:4px"><span class="label">Address</span><span class="value">{{ collect([$w['address_line_1']??null,$w['address_line_2']??null])->filter()->implode(', ') ?: '—' }}</span></td>
      <td colspan="2" style="padding-top:4px"><span class="label">City / State / Pincode</span><span class="value">{{ collect([$w['city']??null,$w['state']??null,$w['pincode']??null])->filter()->implode(', ') ?: '—' }}</span></td>
    </tr>
  </table>
</div>

<div class="section">
  <div class="section-title">Work / Professional Details</div>
  <div style="margin-bottom:5px">
    <span class="label">Services</span>
    <span class="value">@forelse($snapshot['services']??[] as $service)<strong>{{ $service['name'] }}</strong>@if(!$loop->last), @endif @empty—@endforelse</span>
  </div>
  <table class="layout-table">
    <tr>
      <td style="width:33.3%"><span class="label">Experience</span><span class="value">{{ $w['years_of_experience']??'—' }} @if(isset($w['years_of_experience'])) years @endif</span></td>
      <td style="width:33.3%"><span class="label">Preferred Duty</span><span class="value">{{ $w['preferred_duty_type']??'—' }}</span></td>
      <td style="width:33.4%"><span class="label">Preferred Location</span><span class="value">{{ $w['preferred_work_location']??'—' }}</span></td>
    </tr>
    <tr>
      <td><span class="label">Expected Salary</span><span class="value">{{ isset($w['salary_expectation'])&&$w['salary_expectation']!==null?'₹'.number_format((float)$w['salary_expectation'],2):'—' }}</span></td>
      <td><span class="label">Availability</span><span class="value">{{ !empty($w['availability_status'])?str($w['availability_status'])->headline():'—' }}</span></td>
      <td><span class="label">Worker Status</span><span class="value">{{ !empty($w['worker_status'])?str($w['worker_status'])->headline():'—' }}</span></td>
    </tr>
  </table>
  @if(!empty($w['experience_notes']))
    <div style="margin-top:4px;font-size:8px;color:#555"><span class="label">Experience Notes</span><span class="value" style="font-weight:normal;color:#444">{{ $w['experience_notes'] }}</span></div>
  @endif
</div>

<div class="section">
  <div class="section-title">Identity / Verification Details</div>
  <table class="layout-table">
    <tr>
      <td style="width:33.3%"><span class="label">Identity Proof</span><span class="value">@forelse($snapshot['identity_documents']??[] as $identity){{ $identity['document_name']?:str($identity['document_type'])->headline() }}@if(!$loop->last), @endif @empty—@endforelse</span></td>
      <td style="width:33.3%"><span class="label">Identity Proof Number</span><span class="value">@forelse($snapshot['identity_documents']??[] as $identity){{ $identity['document_number']??'—' }}@if(!$loop->last), @endif @empty—@endforelse</span></td>
      <td style="width:33.4%"><span class="label">Concerned Police Station</span><span class="value">{{ $snapshot['internal']['police_station_name']??'—' }}</span></td>
    </tr>
    <tr>
      @php($docStatus = strtolower($snapshot['verification']['document']??'pending'))
      @php($polStatus = strtolower($snapshot['verification']['police']??'pending'))
      @php($bgStatus = strtolower($snapshot['verification']['background']??'pending'))
      @php($overallStatus = strtolower($snapshot['verification']['overall']??'pending'))
      <td><span class="label">Document Verification</span><span class="badge-status badge-{{ $docStatus }}">{{ str($docStatus)->headline() }}</span></td>
      <td><span class="label">Police Verification</span><span class="badge-status badge-{{ $polStatus }}">{{ str($polStatus)->headline() }}</span></td>
      <td><span class="label">Background Verification</span><span class="badge-status badge-{{ $bgStatus }}">{{ str($bgStatus)->headline() }}</span></td>
    </tr>
  </table>
</div>

<div class="section">
  <div class="section-title">Internal Details</div>
  <table class="layout-table">
    <tr>
      <td style="width:33.3%"><span class="label">Supervisor</span><span class="value">{{ $snapshot['internal']['supervisor_name']??'—' }}</span></td>
      <td style="width:33.3%"><span class="label">Executive</span><span class="value">{{ $snapshot['internal']['executive_name']??'—' }}</span></td>
      <td style="width:33.4%"><span class="label">Registration Source</span><span class="value">@php($src=$snapshot['internal']['registration_source']??$w['registration_source']??null)@php($other=$snapshot['internal']['registration_source_other']??$w['registration_source_other']??null){{ !empty($src) ? ($src === 'other' && !empty($other) ? 'Other ('.$other.')' : str($src)->headline()) : '—' }}</span></td>
    </tr>
  </table>
</div>

@php($referenceRows=max(5,count($snapshot['references']??[])))
<div class="section">
  <div class="section-title">References</div>
  <table class="table">
    <thead>
      <tr>
        <th style="width:5%">Sr.</th>
        <th style="width:30%">Name</th>
        <th style="width:25%">Mobile Number</th>
        <th style="width:20%">Relation</th>
        <th style="width:20%">Occupation</th>
      </tr>
    </thead>
    <tbody>
      @for($i=0;$i<$referenceRows;$i++)
        @php($reference=$snapshot['references'][$i]??[])
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ $reference['name']??'' }}</td>
          <td>{{ $reference['mobile_number']??'' }}</td>
          <td>{{ $reference['relation']??'' }}</td>
          <td>{{ $reference['occupation']??'' }}</td>
        </tr>
      @endfor
    </tbody>
  </table>
</div>

<div class="footer">
  <div class="cell" style="width:50%">
    Worker Signature:
    <div class="signature"></div>
    <div style="margin-top:4px">Date: __________________</div>
  </div>
  <div class="cell" style="width:50%;text-align:right">
    Authorized Person: <strong>{{ $snapshot['agency']['authorized_person']??'—' }}</strong><br><br>
    @if($signatureData)<img class="stamp" src="{{ $signatureData }}">@endif
    @if($stampData)<img class="stamp" src="{{ $stampData }}">@endif
    <br>Agency Signature / Stamp
  </div>
</div>
</body></html>
