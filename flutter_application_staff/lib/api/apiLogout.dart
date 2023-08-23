import 'package:http/http.dart' as http;
import 'package:flutter_application_staff/models/Staff.dart';
import 'dart:convert';
import 'dart:io';
class APILogout{
  void logout() async{
    var url = Uri.parse('http://users.blaplafla.me/api/auth/logout');
    final response = await http.post(url, headers: {
      HttpHeaders.authorizationHeader : 'Bearer ${Staff.accessToken}',
      HttpHeaders.acceptHeader: '*/*',
    });
    if (response.statusCode == 200){
      print('logout success');
    }
    else{
      print('logout fail');
    }
  }
}