package com.example.android.hellopets;

import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.preference.PreferenceManager;
import android.support.v7.app.AppCompatActivity;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

import com.android.volley.AuthFailureError;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;
import com.example.android.hellopets.util.QueryUtils;

import java.util.HashMap;
import java.util.Map;

public class LoginActivity extends AppCompatActivity {

    EditText usernameEditText = null;
    EditText passwordEditText = null;
    Button loginButton = null;
    Button registerButton = null;
    SharedPreferences sharedPreferences = null;
    RequestQueue mQueue = null;

    private void initial() {
        usernameEditText = (EditText) findViewById(R.id.username);
        passwordEditText = (EditText) findViewById(R.id.password);
        loginButton = (Button) findViewById(R.id.login);
        registerButton = (Button) findViewById(R.id.register);
        sharedPreferences = PreferenceManager.getDefaultSharedPreferences(LoginActivity.this);
        mQueue = Volley.newRequestQueue(LoginActivity.this);

        loginButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
//发出一条HTTP请求http://10.0.2.2:8080/HappySportServer/UserServlet
//                StringRequest stringRequest = new StringRequest("http://10.0.2.2:8080/HappySportServer/UserServlet", new Response.Listener<String>() {
//                    @Override
//                    public void onResponse(String response) {
//                        Log.d("success", response);
//                        Toast.makeText(MainActivity.this,response,Toast.LENGTH_SHORT).show();
//                    }
//                }, new Response.ErrorListener() {
//                    @Override
//                    public void onErrorResponse(VolleyError volleyError) {
//                        Log.e("fail", volleyError.getMessage(), volleyError);
//                    }
//                });
                final String username = usernameEditText.getText().toString();
                final String password = passwordEditText.getText().toString();
                if (username.equals("") || username == null || password.equals("") || password == null) {
                    Toast.makeText(LoginActivity.this, "用户名和密码不能为空", Toast.LENGTH_SHORT).show();
                } else {
                    String url = "http://" + QueryUtils.ipAddress + ":8080/HappySportServer/UserServlet";
                    final StringRequest stringRequest = new StringRequest(Request.Method.POST, url, new Response.Listener<String>() {
                        @Override
                        public void onResponse(String s) {
                            Log.v("TAG", s);
                            if (s.substring(0, 1).equals("1")) {

                                //登录和注册后把用户信息写入
                                SharedPreferences.Editor editor = sharedPreferences.edit();
                                editor.putString(getString(R.string.user_id), s.substring(1, s.length()));
                                editor.putString(getString(R.string.username), username);
                                editor.putString(getString(R.string.password), password);
                                editor.commit();

                                Toast.makeText(LoginActivity.this, "登录成功", Toast.LENGTH_SHORT).show();
                                Intent intent = new Intent(LoginActivity.this, PetsActivity.class);
                                intent.putExtra("username", username);
                                startActivity(intent);
                            } else {
                                Toast.makeText(LoginActivity.this, "用户名或密码错误", Toast.LENGTH_SHORT).show();
                            }
                        }
                    }, new Response.ErrorListener() {
                        @Override
                        public void onErrorResponse(VolleyError volleyError) {
                            Log.e("TAG", volleyError.getMessage(), volleyError);
                        }
                    }) {
                        @Override
                        protected Map<String, String> getParams() throws AuthFailureError {
                            Map<String, String> map = new HashMap<String, String>();
                            map.put("doWhat", "login");
                            map.put("username", username);
                            map.put("password", password);
                            return map;
                        }
                    };

                    mQueue.add(stringRequest);
                }
            }
        });

        registerButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(LoginActivity.this, RegisterActivity.class);
                startActivity(intent);
            }
        });
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);
        initial();
    }
}
