import 'package:flutter/material.dart';
import 'package:flutter_application_staff/models/Staff.dart';
import 'Login.dart';
import 'package:flutter_application_staff/api/apiLogout.dart';

import 'package:fluttertoast/fluttertoast.dart';

class StaffInforScreen extends StatelessWidget {
  Staff staff;
  final BuildContext? context;
  StaffInforScreen({Key? key, required this.staff, required this.context})
      : super(key: key);
  Widget? staffInformation() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.start,
      children: [
        Container(
          margin: EdgeInsets.only(top: 40, left: 20),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 50,
                backgroundImage: Image.asset('assets/images/user.png').image,
                backgroundColor: Colors.redAccent,
              ),
            ],
          ),
        ),
        Container(
          margin: EdgeInsets.only(top: 50, left: 20),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
              Icon(
                Icons.person,
                size: 30,
              ),
              const SizedBox(
                width: 15,
              ),
              Text(' ${staff.name}'),
            ],
          ),
        ),
        Container(
          margin: EdgeInsets.only(top: 20, left: 20),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
              Icon(
                Icons.email,
                size: 30,
              ),
              const SizedBox(
                width: 15,
              ),
              Text(' ${staff.email}'),
            ],
          ),
        ),
        Container(
          margin: EdgeInsets.only(top: 20, left: 20),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.start,
            children: [
              Icon(
                Icons.phone,
                size: 30,
              ),
              const SizedBox(
                width: 15,
              ),
              Text(' ${staff.phone}'),
            ],
          ),
        ),

      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    // TODO: implement build
    return Scaffold(
      appBar: AppBar(
        title: Text('Staff Information'),
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
              onPressed: () {
                APILogout apiLogout = APILogout();
                apiLogout.logout();
                Staff.accessToken = null;
                Navigator.pushAndRemoveUntil(
                    context, MaterialPageRoute(builder: (context) => Login()), (
                    route) => false);
              },
              icon: Icon(Icons.logout)),
          IconButton(onPressed: () {

            showDialog(
                context: context, builder: (context) => ChangePasswordDiaLog(staff: staff,));
          }, icon: Icon(Icons.settings))
        ],
      ),
      body: Center(
        child: staffInformation(),
      ),
    );
  }
}

class ChangePasswordDiaLog extends StatefulWidget{
  Staff staff;
  ChangePasswordDiaLog({Key? key, required this.staff}) : super(key: key);
  @override
  State<ChangePasswordDiaLog> createState() => _ChangePasswordDiaLogState();
}

class _ChangePasswordDiaLogState extends State<ChangePasswordDiaLog> {
  TextEditingController _currentPasswordController = TextEditingController();
  TextEditingController _newPasswordController = TextEditingController();
  TextEditingController _confirmPasswordController = TextEditingController();
  String _currentPasswordError = '';
  String _newPasswordError = '';

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text('Change Password'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          TextField(
            obscureText: true,
            controller: _currentPasswordController,
            decoration: InputDecoration(
              labelText: 'Current Password',
              icon: Icon(Icons.lock),
              errorText: _currentPasswordError == '' ? null : _currentPasswordError,
            ),
          ),
          TextField(
            obscureText: true,
            controller: _newPasswordController,
            decoration: InputDecoration(

              labelText: 'New Password',
              icon: Icon(Icons.lock),
              errorText: _newPasswordError == '' ? null : _newPasswordError,
            ),
          ),
          TextField(
            obscureText: true,
            controller: _confirmPasswordController,
            decoration: InputDecoration(
              labelText: 'Confirm Password',
              icon: Icon(Icons.lock),
              errorText: _newPasswordError == '' ? null : _newPasswordError,
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () {
            Navigator.of(context).pop();
          },
          child: Text('Cancel'),
        ),
        TextButton(
          onPressed: () async {
            String currentPassword = _currentPasswordController.text;
            String newPassword = _newPasswordController.text;
            String confirmPassword = _confirmPasswordController.text;
            if (currentPassword == '') {
              setState(() {
                _currentPasswordError = 'Current Password is required';
              });
            } else {
              setState(() {
                _currentPasswordError = '';
              });
            }
            if (newPassword == '') {
              setState(() {
                _newPasswordError = 'New Password is required';
              });
            } else {
              setState(() {
                _newPasswordError = '';
              });
            }
            if (confirmPassword == '') {
              setState(() {
                _newPasswordError = 'Confirm Password is required';
              });
            } else {
              setState(() {
                _newPasswordError = '';
              });
            }
            if (newPassword != confirmPassword) {
              setState(() {
                _newPasswordError = 'Confirm Password is not match';
              });
            } else {
              setState(() {
                _newPasswordError = '';
              });
            }
            if (_currentPasswordError == '' && _newPasswordError == '') {
                int i = await widget.staff.changePassWord(currentPassword,  newPassword);
                if (i ==1){
                  Fluttertoast.showToast(
                      msg: "Change Password Success",
                      toastLength: Toast.LENGTH_SHORT,
                      gravity: ToastGravity.CENTER,
                      timeInSecForIosWeb: 1,
                      fontSize: 16.0
                  );
                  Navigator.of(context).pop();
                }
                else{
                  setState(() {
                    _currentPasswordError = 'Something went wrong, please try again';
                    _newPasswordError = 'Something went wrong, please try again';
                  });
                }
            }
          },
          child: Text('OK'),
        ),
      ],
    );
  }
}
