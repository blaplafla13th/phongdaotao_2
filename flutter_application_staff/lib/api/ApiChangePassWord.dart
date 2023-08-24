import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:flutter_application_staff/models/Staff.dart';

class ApiChangePassWord {
  Future<int> changePassWord(String oldPassWord, String newPassWord) async {
    var url = Uri.parse('http://users.blaplafla.me/api/auth/change-password');
    final response = await http.post(url,
        body: json.encode({
          "old_password": oldPassWord,
          "new_password": newPassWord,
        }),
        headers: {
          HttpHeaders.contentTypeHeader: 'application/json',
          HttpHeaders.authorizationHeader: 'Bearer ${Staff.accessToken}',
          HttpHeaders.acceptHeader: '*/*',
        });
    if (response.statusCode == 200) {
      return 1;
    } else {
      return -1;
    }
  }
}
