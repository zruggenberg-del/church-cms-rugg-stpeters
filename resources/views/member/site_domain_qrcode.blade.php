<!-- Front Side -->

    <div class="card">
      <!-- Logo + QR Row -->
      <table style="width: 100%; padding-right: 0; padding-left: 0;">
        <tr style="padding-right:10px;">
         
          <td style="">

            @php
$from = [255, 0, 0];
$to = [0, 0, 255];
                             $url=url('/');
                            
                                    @endphp

         
       

            {!! QrCode::size(120)
                ->color(244, 197, 66)
                ->backgroundColor(31, 36, 48)
                ->margin(1)
                ->generate($url) !!}

        
        <!--  <div class="qr">
              <img
                src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=example.com"
                style="width: 120px; height: auto"
              />
        </div> -->

          </td>

        </tr>
      </table>

      
      <!-- Website Row -->
    </div>