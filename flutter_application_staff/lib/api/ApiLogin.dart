import 'dart:io';

import 'package:http/http.dart' as http;
import 'package:flutter_application_staff/models/Staff.dart';
import 'dart:convert';
import 'DataURL.dart';

class ApiLogin {
  Future<dynamic> login(String email, String password) async {
    var url = Uri.parse('http://users.blaplafla.me/api/auth/login');
    final response = await http.post(url,
        body: json.encode({
          "email": email,
          "password": password,
        }),
        headers: {
          HttpHeaders.contentTypeHeader: 'application/json',
          HttpHeaders.acceptHeader: '*/*',
        });

    if (response.statusCode == 200) {
      var data = json.decode(response.body);
      Staff.accessToken = data['access_token'];
      return await generateStaff(Staff.accessToken!);
    } else {
      return -1;
    }
  }

  Future<dynamic> generateStaff(String accessToken) async {
    var uri = Uri.parse(ApiUrl.USER_MODULE + 'auth/user-profile');
    final respone = await http.get(uri,
        headers: {HttpHeaders.authorizationHeader: 'Bearer $accessToken'});

    if (respone.statusCode == 200) {
      var data = json.decode(respone.body);
      return Staff.fromJson(data);
    } else {
      return -1;
    }
  }
}
