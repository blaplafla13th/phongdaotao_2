import 'package:flutter/material.dart';
import 'package:qr_code_scanner/qr_code_scanner.dart';
import 'package:flutter_application_staff/api/apicheckin.dart';

class AttendanceScreen extends StatefulWidget {
  @override
  State<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends State<AttendanceScreen> {
  ApiCheckin apc = ApiCheckin();
  final GlobalKey qrKey = GlobalKey(debugLabel: 'QR');
  QRViewController? controller;
  String? result = 'chua quet';

  @override
  void dispose() {
    controller?.dispose();
    super.dispose();
  }

  void _onQRViewCreated(QRViewController controller) async {
    this.controller = controller;

    controller.scannedDataStream.listen((event) async {
      setState(() {
        result = event.code;
      });
      controller.pauseCamera();
      final pattern =
          RegExp(r'^(http://supervisor\.blaplafla\.me/api/checkin/join)');
      print(event.code!);
      if (pattern.hasMatch(event.code!)) {
        List<String> respo = await apc.checkIn(event.code!);
        _showAlertDialog(context, respo[0], respo[1], respo[2]);
      } else
        _showAlertDialog(
            context, 'Err', 'Lỗi, không phải Qr code của hệ thống', ' ');
    });
  }

  void _showAlertDialog(
      BuildContext context, String room, String examcode, String supervisorid) {
    showDialog(
        context: context,
        builder: (context) => AlertDialog(
              title: Text('Thông báo'),
              content: Column(children: [
                Text(' $room'),
                SizedBox(
                  height: 15,
                ),
                Text(' $examcode'),
                SizedBox(
                  height: 15,
                ),
                Text(' $supervisorid')
              ]),
              actions: [
                TextButton(
                    onPressed: () {
                      Navigator.of(context).pop();
                      controller?.resumeCamera();
                    },
                    child: Text('OK'))
              ],
            ));
  }

  @override
  Widget build(BuildContext context) {
    // TODO: implement build
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        title: Text('Checkin'),
      ),
      body: Stack(
        alignment: Alignment.center,
        children: [
          QRView(
            key: qrKey,
            onQRViewCreated: _onQRViewCreated,
            overlay: QrScannerOverlayShape(
                borderColor: Colors.white,
                borderRadius: 20,
                borderLength: 50,
                borderWidth: 10),
          ),
          Positioned(
            bottom: 50,
            child: Text(
              "",
              style: TextStyle(
                  fontSize: 18, fontWeight: FontWeight.bold, color: Colors.red),
            ),
          )
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          controller?.toggleFlash();
        },
        child: Icon(Icons.flash_on),
      ),
    );
  }
}
