

import 'package:flutter/material.dart';
import 'package:flutter_application_staff/models/Staff.dart';
import 'MainScreen.dart';
import 'package:flutter_application_staff/api/ApiLogin.dart';
import 'package:url_launcher/url_launcher.dart';

class Login extends StatefulWidget {
  Login({Key? key}) : super(key: key);
  @override
  State<Login> createState() => _LoginState();
}

class _LoginState extends State<Login> {
  bool rememberMe = false;
  String email = '';
  String password = '';
  String _emailErrorText = '';
  String _passwordErrorText = '';
  bool _isPasswordVisible = false;
  void onRememberMeChanged(bool? newValue) {
    setState(() {
      rememberMe = newValue ?? false;
    });
  }

  Future<dynamic> checkLogin(String email, String password) async {
    dynamic a = await ApiLogin().login(email, password);
    if (a.runtimeType == Staff) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => MainScreen(staff: a, context: context,),
        ),
      );
    } else{
      setState(() {
        if (a == -1) {
          _emailErrorText = 'Something went wrong, please try again';
          _passwordErrorText = 'Something went wrong, please try again';
        }
      });
    }
  }
  Future<void> _launchPhoneApp(String phoneNumber) async {
    final Uri phoneUri = Uri(scheme: 'tel', path: phoneNumber);
    if (await canLaunchUrl(phoneUri)) {
      await launchUrl(phoneUri);
    } else {
      throw 'Could not launch ${phoneUri.toString()}';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SingleChildScrollView(
        child: Column(
          children: [
            Container(
              margin: EdgeInsets.only(top: 200),
              padding: const EdgeInsets.symmetric(horizontal: 40),
              child: TextField(
                decoration: InputDecoration(
                  errorText: _emailErrorText == '' ? null : _emailErrorText,
                  prefixIcon: Icon(Icons.email),
                  border: const OutlineInputBorder(
                    borderRadius: BorderRadius.all(Radius.circular(20)),
                  ),
                  label: const Text('Email'),
                ),
                onChanged: (value) {
                  email = value;
                  print('Email: $email');
                },
              ),
            ),
            Container(
              margin: EdgeInsets.only(top: 20),
              decoration: BoxDecoration(borderRadius: BorderRadius.circular(20)),
              padding: const EdgeInsets.symmetric(horizontal: 40),
              child: TextField(
                obscureText: !_isPasswordVisible,
                decoration: InputDecoration(
                  errorText: _passwordErrorText == '' ? null : _passwordErrorText,
                  prefixIcon: Icon(Icons.lock),
                  suffixIcon: IconButton(
                    icon: Icon(
                      _isPasswordVisible
                          ? Icons.visibility
                          : Icons.visibility_off,
                    ),
                    onPressed: () {
                      setState(() {
                        _isPasswordVisible = !_isPasswordVisible;
                      });
                    },
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.all(Radius.circular(20)),
                  ),
                  labelText: 'Password',
                ),
                onChanged: (value) {
                  password = value;
                  print('Password: $password');
                },
              ),
            ),
            Container(
                margin: const EdgeInsets.symmetric(horizontal: 40),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Checkbox(
                      value: rememberMe,
                      onChanged: onRememberMeChanged,
                    ),
                    Text('Remember me'),
                    const Spacer(),
                    TextButton(
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text('This feature is not available yet'),
                            duration: Duration(seconds: 2),
                          ),
                        );
                      },
                      child: Text('Forgot Password'),
                    )
                  ],
                )),
            Container(
                margin: const EdgeInsets.symmetric(horizontal: 50, vertical:30),
                padding: const EdgeInsets.only(top: 20),
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(20),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 40),
                  ),
                  onPressed: () {
                    //checkLogin(email, password);
                    checkLogin(email, password);
                  },
                  child: Text('Login'),
                )),
            Container(
              margin: EdgeInsets.only(top: 20),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('Hava a problem?'),
                  TextButton(
                    onPressed: () {
                    _launchPhoneApp('+84796421201');
                    },
                    child: Text('Contact us'),
                  )
                ],
              ),
            ),

          ],
        ),
      ),
    );
  }
}
